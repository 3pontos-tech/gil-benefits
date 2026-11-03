---
type: adr
title: "Companies and people with history are never hard-deleted; the database refuses"
module: company
status: accepted
date: 2026-11-03
supersedes: none
affects:
  - company
  - appointments
  - billing
  - credits
  - vouchers
  - consultants
  - panel-admin
  - user
---

# ADR: Companies and people with history are never hard-deleted

**Status:** accepted · **Date:** 2026-11-03 · **Decided by:** Richard (product/tech)

Closes audit findings tenant-v01, company-v01, dados-14, panel-admin-07, credits-08
and appointments-v03, and the owner-cascade part of company-07.

## Context

Almost every table that points at `companies` or `users` was created with
`on delete cascade`. A hard delete of a company would silently remove its
appointments (with records and feedback), contracts, credit orders and grants,
credits, voucher batches, departments and the profiles of its people. The owner
column `companies.user_id` cascaded too, so hard-deleting the person who owns a
company would remove the company and everything under it. `appointments.user_id`
and `appointments.consultant_id` had no rule at all, so hard-deleting a person or
a consultant with appointments failed with a database error.

Nothing in the product ever needed a hard delete. The admin offered "delete
permanently" for users (removed in PR #324), companies and consultants; the
company one already failed since #324, because the membership foreign key is
`restrict` and every company has memberships. Soft delete and restore are what
people use.

## Decisions

**D1. Rows that record money or care history keep their company and their person.**
Foreign keys to `companies` from `appointments`, `company_plans`, `credit_grants`,
`credit_orders`, `user_credits` and `voucher_batches`, and to `users` from
`companies.user_id`, `appointments.user_id`, `user_credits.owner_id` and
`user_credits.holder_id`, are `on delete restrict`. `appointments.consultant_id`
is `restrict` as well. A hard delete of a company, a person or a consultant with
any of these rows is refused by the database, from any code path, including the
console.

**D2. A person's profile outlives their company.** `user_details.company_id` is
`on delete set null`: the profile belongs to the person, not to the employer.

**D3. Dependent rows without value of their own keep cascading.** Departments,
appointment feedback and records, document states, anamneses, redemptions.
Authorship columns (`created_by`, `admin_user_id`, `cancelled_by`) keep
`set null`.

**D4. The admin offers no "delete permanently".** Companies and consultants join
users: the admin has soft delete and restore only. The actions were removed
rather than guarded, because the only outcome they could still produce is a
constraint error.

**D5. Real erasure is anonymisation, decided later.** A data-subject request
(LGPD) will be served by an offboarding action that pseudonymises name, e-mail
and notes while keeping the rows. That is a separate decision and PR; until it
exists, nothing is erased.

## Consequences

- The migration only changes constraint rules; no row is touched. A database
  built from scratch gets the same rules.
- `Company::forceDelete()` and `User::forceDelete()` throw a `QueryException`
  whenever history exists. Tests that need a hard delete create a record with no
  history first.
- Soft-deleting a company still releases its people (ADR 2026-10-30) and keeps
  every historical row readable through `withTrashed()`.
- The default bucket keeps its id for the single-employer index: it cannot be
  hard-deleted while it has members, credits or appointments.
