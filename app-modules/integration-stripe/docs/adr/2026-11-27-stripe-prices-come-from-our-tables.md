---
type: adr
title: "Stripe charges the prices and discounts defined in our tables"
module: integration-stripe
status: accepted
date: 2026-11-27
supersedes: none
affects:
  - integration-stripe
  - billing
  - discounts
  - credits
---

# ADR: Stripe charges the prices and discounts defined in our tables

**Status:** accepted · **Date:** 2026-11-27 · **Decided by:** Richard (product/tech)

## Context

Under Cashier the catalog lived in Stripe and was imported into our tables
(`billing:sync-stripe`). When billing moved to other gateways, the seat prices moved into
`SeatPricingTierEnum` and the person prices into `billing_plan_prices`. Bringing the
catalog back to Stripe as the source would tie the next gateway change to a catalog it
cannot read.

## Decisions

**D1. The source is ours; the direction is always our tables → Stripe.**

| What | Where the amount comes from | What Stripe receives |
| --- | --- | --- |
| Company subscription | `SeatPricingTierEnum` for the number of seats | inline `price_data` per seat, `quantity` = seats |
| Person subscription | `billing_plan_prices` | a catalog price created by `stripe:plan:create` |
| Credits | `credits.price_in_cents` | inline `price_data`, `quantity` = credits |

The seat count is fixed at checkout and never synced afterwards, as it was before.

**D2. Only monthly plans.** Every subscription price is `recurring.interval = month`.

**D3. `stripe:plan:create` writes our row first and creates the Stripe object from it.**
For a person plan it creates the product and the price in Stripe and stores their ids. A
company plan creates nothing in Stripe. Stripe prices are immutable, so a new amount
creates a new price and deactivates the old row, which stays for the subscriptions that
point to it.

**D4. Discounts stay in the `discounts` module.** The adapter mirrors the resolved
`CheckoutDiscount` in a single-use Stripe coupon (`forever`, or `repeating` for N months),
and the subscription row keeps `discount_percent` / `discount_amount_cents` as before.
Stripe promotion codes are not used.

## Consequences

* Changing a price is a change in our tables (and a new Stripe price for person plans),
  never an edit in the Stripe dashboard.
* A new gateway reads the same tables; nothing has to be exported from Stripe.
* Coupons pile up in the Stripe account, one per discounted checkout.
