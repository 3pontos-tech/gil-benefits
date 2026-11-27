---
type: adr
title: "Subscription columns keep their stripe_* names after Cashier leaves; renaming is recorded debt"
module: billing
status: accepted
date: 2026-11-27
supersedes: none
affects:
  - billing
  - integration-stripe
  - panel-admin
  - panel-app
  - panel-company
  - user
---

# ADR: Subscription columns keep their stripe_* names after Cashier leaves

**Status:** accepted · **Date:** 2026-11-27 · **Decided by:** Richard (product/tech)

Records the schema part of audit finding arq-08 as accepted debt, decided while
planning the return to Stripe without Laravel Cashier (issue #319).

## Context

The billing schema was born from Cashier's migrations. `billing_subscriptions`
stores the gateway's subscription id, its status and its price in `stripe_id`,
`stripe_status` and `stripe_price`, and Cashier's `Billable` trait added
`stripe_id`, `pm_type`, `pm_last_four` and `trial_ends_at` to `users` and
`companies`. Cashier also created `billing_subscription_items`.

Every gateway after Stripe wrote into the same columns: Barte and Virtu store
their own ids and statuses in `stripe_id` and `stripe_status`, and the code that
decides access, revenue and company status reads them by those names.

While Cashier is installed the names cannot change. Its `Subscription` model and
its webhook controller read `stripe_status`, `stripe_id` and `stripe_price`
directly, so a rename would break the access rule at once. Turning the status into
an enum has the same problem: Cashier compares the column with plain strings.

Once Cashier is gone, nothing requires the old names. The columns become ordinary
columns that the gateway adapters write and the billing core reads, and the
`users`/`companies` columns are no longer read or written by anyone.

## Decisions

**D1. The subscription columns keep their names.** `billing_subscriptions.stripe_id`,
`stripe_status` and `stripe_price` stay as they are when Cashier leaves. The
Stripe adapter writes them the same way Barte and Virtu did.

**D2. Cashier's leftover columns stay.** `users` and `companies` keep `stripe_id`,
`pm_type`, `pm_last_four` and `trial_ends_at`, and `billing_subscription_items`
stays, empty and without a model (the unused `SubscriptionItem` model is deleted).
They are unused after the `Billable` trait goes. The model PHPDoc keeps listing them
while they exist.

**D3. When the debt is paid, these are the target names.**

| Today | Target | Why not just drop the prefix |
| --- | --- | --- |
| `stripe_status` | `status` | nothing collides |
| `stripe_id` | `provider_subscription_id` | `id` is the primary key |
| `stripe_price` | `provider_price_id` | `price` would shadow the `price()` relation; the target also matches `billing_plan_prices.provider_price_id`, the column it joins on, and `billing_customers.provider_customer_id` |

The same change drops the `users`/`companies` columns and
`billing_subscription_items`, and introduces a status enum owned by billing that
each gateway translates its own vocabulary into.

**D4. The rename is its own pull request, from `develop`, after Cashier is gone.**
It is not part of the Stripe return. Doing it there would roughly double that
pull request with a mechanical change that is not needed for Stripe to work.

## Consequences

* Nothing breaks: the names are misleading, not wrong. With Stripe back as the only
  gateway they even match the gateway again.
* The cost shows up at the next gateway change: a new adapter would write its own
  ids into `stripe_id` and its statuses into `stripe_status`, as Virtu and Barte did.
  That is the moment to pay this debt, together with any other schema change in
  billing.
* A reader of `Subscription` or of the `User`/`Company` PHPDoc may take the
  `stripe_*` names as a sign that the domain depends on Stripe. It does not: the
  only code that talks to Stripe lives in `integration-stripe`.
