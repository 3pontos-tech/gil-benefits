---
type: adr
title: "One responsible person per company; owner memberships grant access; the responsible changes only through a transfer action"
module: company
status: accepted
date: 2026-11-09
supersedes: none
affects:
  - company
  - tenant
  - credits
  - panel-admin
  - panel-company
---

# ADR: Company ownership lives in two places with two meanings

**Status:** accepted · **Date:** 2026-11-09 · **Decided by:** Richard (product/tech)

Closes audit findings company-01 (critical) and tenant-04 (high). Builds on the
membership decisions of 2026-10-29 recorded in the single-active-employer ADR.

## Context

A company says "owner" in two places:

- `companies.user_id`, the responsible person. This is the holder of the company's
  credit pool (`user_credits.owner_id` / `holder_id`), the buyer at checkout and the
  recipient of payment webhooks.
- `company_employees.role = company_owner`, the access role in the management panel.
  Several people can hold it.

`User::tenantRole()` answers owner when `user_id` matches and otherwise reads the pivot.
Nothing kept the two in sync. The admin changed `user_id` on the company edit form; the
only reaction was a credits observer that moved the pool. The previous owner's pivot row
stayed `company_owner`, so they remained an owner of the company they no longer held.
Anyone who could still reach the management panel, for instance by registering a new
company, could open the old tenant and manage its members.

## Decisions

**D1. `companies.user_id` stays and means "responsible".** One person per company. It is
the pool holder, the buyer and the billing contact. Removing the column or deriving the
responsible from the pivot was considered and rejected on 2026-10-29.

**D2. `company_owner` in the pivot means "owner-level access".** Several people may have
it. `tenantRole()` keeps treating a pivot owner as an owner even when they are not the
responsible; demotion is a write, not a reading rule. Known gap, out of scope here: the
company panel's tenant list (`User::getTenants()`) shows the responsible's companies and
manager memberships only, so a pivot owner who is not the responsible reaches the tenant
by URL but does not see it in the switcher.

**D3. The responsible changes only through `TransferCompanyOwnership`.** The action
moves `user_id`, gives the new owner an active owner membership (upgrading an existing row
or creating one) and downgrades the previous owner's owner row to `company_manager`. It
then dispatches `CompanyOwnershipTransferred`. The admin form offers the owner select only
on creation; the edit page has a "transfer ownership" action instead.

**D4. Transferring is not removing.** The previous owner's owner membership, when there
is one, becomes a manager membership: day-to-day access without member administration.
A responsible without a membership (a soft-deleted account, an inconsistent row) gets
none; the action never creates access for the person who is leaving the role. Removing them from the company
is a separate decision, taken afterwards on the members list. The action's docblock and
the admin confirmation say so.

**D5. Consumers react to the event, not to the column.** Credits moves the company pool
to the new responsible from a listener on `CompanyOwnershipTransferred`, discovered by
convention. The `CompanyCreditsObserver` on `Company::updated` is gone, so a stray
`user_id` write no longer has side effects and the model no longer imports the credits
module.

**D6. Other owner memberships are left alone.** Only the previous responsible is
downgraded. People who hold owner-level access without being the responsible keep it.

## Consequences

- The exploit path closes: after a transfer the previous owner is a manager of the old
  tenant and the new owner is its only responsible, in both places.
- The previous owner, now a manager, counts as an active member wherever the company
  excludes only the responsible (`Company::onlyEmployees()`): seat counter, credit
  distribution, adoption metrics. Managers already counted there; the transfer adds one.
- The seeder attaches the responsible as the pivot owner, so development data follows
  the same rule.
- Production data had no divergent rows when checked on 2026-11-09, so no data migration
  ships with this change.
- Accepted debt, out of scope here: the credit pool belonging to the company rather than
  to a person (arq-07), and tenant-08.
