---
type: adr
title: "Cancellation at period end and the Stripe customer portal"
module: integration-stripe
status: accepted
date: 2026-11-27
supersedes: none
affects:
  - integration-stripe
  - panel-app
  - panel-company
---

# ADR: Cancellation at period end and the Stripe customer portal

**Status:** accepted · **Date:** 2026-11-27 · **Decided by:** Richard (product/tech)

## Decisions

**D1. Who cancels.** The company owner cancels the company subscription; a person cancels
their own (B2C, or an employee paying for themselves).

**D2. Cancellation is at the end of the paid period.** `cancelSubscription()` sets
`cancel_at_period_end`. The row stays `active` and the webhook writes the period end into
`ends_at`; access lasts until then and ends when Stripe deletes the subscription. This is
what Cashier did, verified in its source.

**D3. The customer portal handles invoices, card, billing details and cancellation.** Two
portal configurations, one for companies and one for people, with no plan switching and no
pause. Their ids are `STRIPE_COMPANY_PORTAL_CONFIGURATION` and
`STRIPE_USER_PORTAL_CONFIGURATION`.

**D4. Someone who never paid through Stripe does not get the portal.** The Stripe customer
is created at the first checkout or credit purchase, never when a panel loads, so a
subsidised employee who pays nothing has no customer and sees the panel's own billing
page instead of an empty portal.

## Consequences

* Plan switching is not offered anywhere yet (an open question).
* A person who cancels keeps the benefit until the end of what they paid for.
