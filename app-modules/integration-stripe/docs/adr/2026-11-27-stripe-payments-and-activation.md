---
type: adr
title: "Card and Pix Automático; access follows the subscription status read from Stripe"
module: integration-stripe
status: accepted
date: 2026-11-27
supersedes: none
affects:
  - integration-stripe
  - billing
  - credits
---

# ADR: Card and Pix Automático; access follows the subscription status read from Stripe

**Status:** accepted · **Date:** 2026-11-27 · **Decided by:** Richard (product/tech)

## Context

Pix Automático works with Checkout in subscription mode: the buyer authorises a mandate
with a maximum amount per cycle (Stripe's default is R$ 400), and each charge is announced
three days before and can take up to seven days to confirm. A Pix payment is therefore
asynchronous: the checkout session can complete before the money arrives. A single Pix
payment is limited to the equivalent of US$ 3,000. Boleto is not wanted. There is no
invoice (nota fiscal) integration.

## Decisions

**D1. Card and Pix, no boleto.** Checkout sends `allowed_payment_method_types`
(`payment_method_types` was removed in API version `2026-09-30.endive`). Above
`STRIPE_PIX_MAX_AMOUNT_CENTS` the checkout offers card only.

**D2. The Pix mandate cap is the full plan price, without the discount.** When a
temporary discount ends, the next charge must still fit under the mandate.

**D3. The webhook is a notice.** The handler reads the subscription or the checkout
session again from the API before translating it, and each event id is processed once.

**D4. Access follows the subscription status, not the checkout.**

| Event | What happens |
| --- | --- |
| `checkout.session.completed`, subscription | the pending row moves to its `sub_…` id; access only if the API already says `active` |
| `customer.subscription.created` / `updated` | `active`/`trialing` → `SubscriptionActivated` (with `ends_at` when cancelling at period end); `past_due`/`unpaid` → `SubscriptionDefaulted`; `incomplete` stays `pending` |
| `customer.subscription.deleted` | `SubscriptionCancelled`; access ends |
| `checkout.session.completed`, payment, `paid` | `OrderCreditPurchased` (card) |
| `checkout.session.async_payment_succeeded` | `OrderCreditPurchased` (Pix) |

The plan listed `invoice.paid` as the activation signal. It is not needed: the first
payment and every recovery change the subscription status, and `customer.subscription.updated`
carries that change. Renewals do not change the status and need no event.

**D5. The pending row is correlated by a reference we generate.** The checkout writes the
row with `checkout_<uuid>` and sends the same value in the subscription metadata; the
upsert moves the row to the `sub_…` id the first time Stripe reports it, so a checkout
never leaves two rows.

**D6. The Stripe request shape lives in our DTOs.** The adapter builds
`CheckoutSessionDTO`, `CheckoutLineItemDTO` and `CouponDTO`; only their `toArray()` knows
the parameter names Stripe expects, and `StripeApi` is the only class that calls the SDK.
An API change like the removal of `payment_method_types` is fixed in one place.

**D7. Billing e-mails are Stripe's.** Payment receipts, failed-payment and Pix pre-debit
notices are configured in the Stripe account; we send none.

## Consequences

* A Pix subscription shows as pending until Stripe confirms the first payment.
* The webhook endpoint is registered once per environment by `stripe:webhook:create`, from
  `StripeWebhookEventEnum`, the same list the handler matches and `make stripe-listen`
  forwards. The endpoint is pinned to the SDK's API version.
* The SDK pins API version `2026-10-28.endive` (stripe-php 23.0); upgrading it is a
  deliberate change, because subscription periods and payment method parameters changed
  between versions.
