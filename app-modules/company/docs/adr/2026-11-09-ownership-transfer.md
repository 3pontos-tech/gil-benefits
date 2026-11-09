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
membership decisions of 2026-10-29 taken in #324 (arq-06): `companies.user_id` stays,
several owner memberships are allowed.

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
responsible from the pivot was considered and rejected in #324.

**D2. `company_owner` in the pivot means "owner-level access".** Several people may have
it. `tenantRole()` keeps treating a pivot owner as an owner even when they are not the
responsible; demotion is a write, not a reading rule. Known gap, out of scope here: the
company panel's tenant list (`User::getTenants()`) shows the responsible's companies and
manager memberships only, so a pivot owner who is not the responsible reaches the tenant
by URL but does not see it in the switcher.

**D3. The responsible changes only through `TransferCompanyOwnership`.** The action
moves `user_id`, gives the new owner an active owner membership (upgrading an existing row
or creating one) and applies the chosen outcome to the previous owner's membership (D4).
It then dispatches `CompanyOwnershipTransferred`. The admin form offers the owner select
only on creation; the edit page has a "transfer ownership" action instead, hidden on the
default bucket. The admin's "assign role" action on the members list is hidden for the
responsible, so nothing else writes a lesser role to their pivot row. `user_id` remains
fillable: a direct `update(['user_id' => ...])` is possible in code and would leave the
pool and the pivot behind. No code does it today; the rule is enforced by review.

**D4. Transferring never deletes a membership; the admin decides what the previous
owner keeps.** The action takes a `PreviousOwnerOutcome`, chosen in the modal with no
default:

- *Keep as manager*: the owner membership becomes a manager membership, day-to-day
  access without member administration. This is an access grant, not a reduction: a
  pivot owner who was not the responsible of any other company did not pass the company
  panel gate before, and as a manager they do. The admin panel cannot revoke it later
  (its members list has no deactivate or detach), so the choice has to be made here.
- *Revoke access*: the membership is deactivated (`active = false`), whatever its role.
  `tenantRole()` ignores inactive rows, so the person no longer sees the company and
  cannot open its tenant. The row stays and can be reactivated from the company panel.
  This is the outcome for the common case, the HR contact who left the company.

A responsible without a membership (a soft-deleted account, an inconsistent row) gets
none; the action never creates access for the person who is leaving the role. The event
carries the outcome so consumers can tell the two apart.

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
- When kept as manager, the previous owner counts as an active member wherever the
  company excludes only the responsible (`Company::onlyEmployees()`): seat counter,
  credit distribution, adoption metrics. Managers already counted there; the transfer
  adds one. When access is revoked, the inactive row counts nowhere.
- The pool migration moves every credit whose owner and holder are the previous
  responsible, in any status, including used ones and any in use in their own
  appointments. History then attributes them to the new responsible. This predates the
  change (arq-07, accepted debt) and now matters more when the previous owner stays in
  the company as a manager.
- The seeder attaches the responsible as the pivot owner, so development data follows
  the same rule.
- No data migration ships with this change.
- Accepted debt, out of scope here: the credit pool belonging to the company rather than
  to a person (arq-07), and tenant-08.
