---
type: adr
title: "A delinquent subscription keeps access until the gateway cancels it"
module: billing
status: accepted
date: 2026-11-27
supersedes: none
affects:
  - billing
  - integration-stripe
  - integration-virtu
  - panel-admin
---

# ADR: A delinquent subscription keeps access until the gateway cancels it

**Status:** accepted · **Date:** 2026-11-27 · **Decided by:** Richard (product/tech)

## Context

When a renewal fails, the gateway keeps retrying for a while (Stripe Smart Retries) and
only then gives up. Stripe reports the retry window as `past_due` (and `unpaid`). Our
gateways translate it into `defaulter`, which was in `STATUSES_WITHOUT_ACCESS`: the person
lost access at the first failed attempt, often a card that just needed another try.

Three options were discussed: cut access on the first failure, keep a fixed grace period
of our own, or keep access while the gateway retries and cut it when the gateway cancels.

## Decision

**Keep access while the gateway retries (option C).** `defaulter` leaves
`STATUSES_WITHOUT_ACCESS` and counts as granting access, like `active`, while no end date
in the past says otherwise. Access ends when the gateway cancels the subscription
(`customer.subscription.deleted` on Stripe), which becomes `inactive`.

How long the retry window lasts is configured in the gateway (on Stripe: retries ending in
"cancel the subscription"), not in our code.

## Consequences

* A failed renewal no longer cuts access at once; the person has the retry window to fix
  the payment, with the gateway's dunning e-mails.
* The admin still sees the company as delinquent: `CompanyStatusResolver` maps
  `defaulter` to "inadimplente" regardless of access.
* `defaulter` rows count in `SubscriptionBuilder::grantingAccess()` and in revenue-style
  queries that use it.
* If the gateway is configured to leave a subscription `unpaid` forever instead of
  cancelling it, access never ends. The Stripe account must end retries with a cancellation.
