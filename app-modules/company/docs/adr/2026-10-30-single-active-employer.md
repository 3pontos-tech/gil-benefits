---
type: adr
title: "A person has at most one active employer; owner and manager memberships do not count"
module: company
status: accepted
date: 2026-10-30
supersedes: none
affects:
  - company
  - tenant
  - api
  - panel-admin
  - panel-company
  - user
---

# ADR: A person has at most one active employer

**Status:** accepted · **Date:** 2026-10-30 · **Decided by:** Richard (product/tech)

Closes phase 4 of audit finding arq-06. Phases 1 to 3 (primary key, foreign keys,
unique pair, mandatory role, unified reading of `company_employees`) shipped in PR #324.

## Context

`company_employees` lets one person hold any number of active memberships. Reading
code had to guess which one is "the employer": `User::employerCompanyId()` and
`ResolveAppAccessAction` pick the newest active membership outside the default bucket.
Two active employers only happen when a company forgets to deactivate someone who
changed jobs, and the guess is invisible to the admin.

Every user is also a member of the default bucket (`flamma-company`), which is not an
employer: it is the shared tenant for people without one.

## Decisions

**D1. One active employer per person.** A person can have at most one membership that is
active, not soft-deleted, outside the default bucket and with role `employee`.

**D2. Owner and manager memberships do not count.** The rule is about who receives the
benefit. Someone can own or manage company A and be an employee of company B; the
company panel already restricts them to A, and the app already shows both. Two
`employee` memberships are what the rule forbids.

**D3. The bucket is outside the rule.** Membership in `flamma-company` never conflicts
with an employer, because the bucket is the continuity tenant, not an employer.

**D4. Conflicts are refused, never resolved silently.** Attaching or reactivating an
employee membership while another is active fails with a domain error that each entry
point turns into a message ("already active at another company, deactivate there
first"). Silent transfer would hide registration mistakes; changing employer is rare and
is an explicit action of the former company.

**D5. The "newest wins" heuristic goes away.** With D1 there is nothing to pick between.
`User::employerCompanyId()` and `ResolveAppAccessAction` stop ordering by membership
date; the employer is the single active employee membership, and the bucket is the
fallback.

## Where the rule lives

Two layers, on purpose:

- **Database**, as a partial unique index on `user_id` where `active`, `deleted_at is null`,
  `role = 'employee'` and `company_id <> <bucket id>`. The bucket id is a literal resolved by
  the migration from the slug, because a partial index predicate cannot run a subquery.
  The migration only creates the index when the bucket row exists; a fresh database
  (test suite) has no bucket at migration time and cannot create one there, since
  `companies.user_id` is mandatory. Production has the bucket, so production gets the
  index.
- **Application**, in the `TenantMember` pivot model, checked on create and on
  reactivation. This layer runs in every environment, is what the tests cover, and is
  what produces the user-facing message instead of a constraint violation.

## Consequences

- Entry points that create memberships handle the domain error: company panel
  "create and attach", admin role assignment, spreadsheet import, API registration.
  Company creation attaches the owner, which D2 leaves outside the rule.
- Tests that modelled a person with two employee memberships are rewritten to the
  owner-plus-employee shape or to the refusal.
- Nobody in production currently holds two active employee memberships, so the
  migration needs no data fix.
