---
type: adr
title: "A gateway lives only in an integration module; access is read from our own rows"
module: billing
status: accepted
date: 2026-11-27
supersedes: none
affects:
  - billing
  - integration-stripe
  - integration-virtu
  - integration-barte
  - panel-app
  - panel-company
  - company
---

# ADR: A gateway lives only in an integration module; access is read from our own rows

**Status:** accepted · **Date:** 2026-11-27 · **Decided by:** Richard (product/tech)

Closes issue #319. Decided while planning the return to Stripe as the only gateway.

## Context

Billing changed gateway several times in a short period: Stripe, then Barte, then Virtu,
and now Stripe again. Barte and Virtu were added the decoupled way, as integration modules
that register a driver on `BillingManager`. Stripe never was: it came through Laravel
Cashier, and Cashier reached into the domain.

* `Subscription` and `SubscriptionItem` extended Cashier's models, so the access rule was
  Cashier's `valid()` plus our own overrides.
* `User` and `Company` used the `Billable` trait.
* `BillingServiceProvider` configured Cashier per Filament panel and replaced its webhook
  controller; that webhook wrote `billing_subscriptions` directly, skipping the domain
  events every other gateway goes through.

`BillingContract` also asked each gateway whether a billable had access
(`isSubscribed()`, `hasActiveSubscription()`). Every implementation answered by reading
`billing_subscriptions`, with a different rule each: the Stripe adapter used `valid()`,
Barte and Virtu accepted only `stripe_status = 'active'`. Answering a question about our
own rows needed the gateway's driver loaded.

## Decisions

**D1. Cashier leaves the domain.** `laravel/cashier` is removed. `Subscription` extends
`Model` and owns its access rule. `User` and `Company` drop `Billable`. The Cashier
webhook, the Stripe adapter inside billing, `billing:sync-stripe`, `billing:sync-customers`,
`ConfigPlanRepository` and `config/cashier.php` are deleted. An architecture test keeps
every domain module free of `Laravel\Cashier` and of the `Stripe` SDK.

**D2. A gateway lives only in an integration module.** Each gateway is an
`integration-<gateway>` module that registers its driver with `BillingManager::extend()`,
talks to the gateway with its own client, and translates the gateway's webhooks into the
billing domain events. Stripe comes back as `integration-stripe`, using `stripe/stripe-php`
directly. Billing owns the contract, the schema and the rules; it contains no gateway code.

**D3. `BillingContract` keeps only what talks to the gateway.** `isSubscribed()` and
`hasActiveSubscription()` leave the contract. What remains is `ensureCustomerExists()`,
`createCheckout()`, `checkoutOpensInNewTab()` and `billingPortal()`, plus the capability
interfaces a gateway may implement (`SupportsSubscriptionCancellation`,
`SupportsCreditPurchase`, `SupportsCheckoutDiscount`, `SupportsLimitedDurationDiscount`).

**D4. Access is decided once, in the billing core, from our own rows.**
`Subscription::valid()` answers for a row and `SubscriptionCollection` answers for an
owner (`$billable->subscriptions->grantsAccessTo($slug)` / `->grantsAnyAccess()`). A row
grants access when its status is not in `STATUSES_WITHOUT_ACCESS` and one of these holds:

| Holds | Example |
| --- | --- |
| status `active` and the paid period not over | a running subscription, or one cancelled at period end |
| trial end in the future | `trialing` during the trial |
| end date in the future | `canceled` or `past_due` while the paid period still runs |

This is Cashier's rule with two gaps closed: a `canceled` row without an end date and a
`trialing` row past its trial no longer grant access. Under Cashier they did, because its
`active()` only listed the statuses to refuse.

**D5. The webhook is a notice, not the source of truth.** An integration module that
receives a gateway webhook reads the current state from the gateway's API before
translating it into a domain event, so retries and out-of-order deliveries cannot write a
stale status.

**D6. One gateway customer per payer.** The payer is the company, or the user when the
user pays for themselves. The mapping lives in `billing_customers`; nothing is written to
the Cashier columns on `users` and `companies`.

## Consequences

* Changing gateway means writing an integration module and switching
  `BillingProviderEnum::checkoutCases()`. The domain, the access rule and the panels do
  not change.
* The rule that decides access is the same whatever gateway sold the subscription. A
  status a gateway introduces grants access only through the dates above, so a new
  gateway must translate its vocabulary into the domain events instead of relying on its
  own status names.
* Access checks no longer resolve a driver, so they work for subscriptions sold by a
  gateway whose module is gone.
* Behaviour that changes with this decision: a company subscription in `incomplete` no
  longer counts as paying (the old Stripe adapter counted it), and Barte/Virtu rows in
  `trialing`, or `canceled` with a future end date, now grant access (their adapters
  refused anything but `active`). Neither case occurs in the data these gateways write.
* The `stripe_*` column names stay for now; see
  `2026-11-27-gateway-column-names-kept-as-debt`.
