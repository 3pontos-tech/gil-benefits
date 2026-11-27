---
type: adr
title: "Virtu and Barte leave the code; their rows stay as history"
module: billing
status: accepted
date: 2026-11-27
supersedes: none
affects:
  - billing
  - credits
  - panel-admin
  - panel-company
  - panel-app
---

# ADR: Virtu and Barte leave the code; their rows stay as history

**Status:** accepted · **Date:** 2026-11-27 · **Decided by:** Richard (product/tech)

## Context

With Stripe as the only gateway, `integration-virtu` and `integration-barte` have nothing
left to sell or receive. They wrote rows that are still in the database: plans and prices
with `provider` `virtu`/`barte`, subscriptions on those prices, customers in
`billing_customers`, credit orders and inbound webhooks.

## Decisions

**D1. Both modules are deleted**, with their routes, commands, config, environment
variables and tests.

**D2. Their data is not deleted.** No migration touches those rows. The `Virtu` and
`Barte` cases stay in `BillingProviderEnum` and `InboundWebhookSourceEnum` so the stored
values keep reading, but they are in neither `activeCases()` nor `checkoutCases()`.

**D3. Nothing resolves a driver for a retired gateway.** `BillingCustomer::getActiveProvider()`
only answers providers in `activeCases()`, and the cancel action hides itself for a
subscription whose gateway was retired. Plans of a retired gateway no longer count when
checking access.

## Consequences

* A subscription sold through Virtu or Barte no longer grants access. None was active
  when this was decided.
* Reports that read history (revenue, financial cockpit) still see those rows.
* Cleaning up the old rows, if ever wanted, is a separate decision.
