---
type: adr
title: "The admin books an appointment for anyone, with or without quota, credit or an open appointment"
module: appointments
status: accepted
date: 2026-11-03
supersedes: none
affects:
  - appointments
  - credits
  - panel-admin
---

# ADR: The admin books without eligibility checks

**Status:** accepted · **Date:** 2026-11-03 · **Decided by:** product owner, recorded by Richard (product/tech)

Closes audit findings panel-admin-v01 and dados-v04 as a product decision, and
confirms why arq-10 was dropped on 2026-10-28.

## Context

Employees book through `ScheduleAppointmentForUserAction` (app panel and API). It locks
the user row and refuses when `User::canCreateAppointment()` is false: no quota left in
the cycle and no available credit at the employer, or an appointment still open, since
the rule is one at a time.

The admin's `CreateAppointment` page does none of that. It reads the remaining quota
without a lock, records whether this appointment will consume a credit, saves the
appointment as `Pending` and fires `AppointmentReserved`. The credits module consumes a
credit when the quota is gone and a credit exists; when none exists, nothing happens and
the appointment stands. The only limit the form enforces is the date: the picker refuses
a time in the past, on the client and in validation. The consultant is picked later, at
confirmation, among those free at that time.

The audit read this as a hole: an appointment created with no quota and no credit, with
no message to the admin and no trace of why. The product owner read it as the feature.

## Decisions

**D1. The admin is free to book.** An appointment created from the admin panel is not
subject to quota, credit or the one-open-appointment rule. The admin is the operator's
hand: courtesy sessions, recovering a failed booking, a company that asked for an
exception. None of those should be blocked by a balance.

**D2. The only limit is the calendar.** The appointment must be in the future. The
consultant must be free at that time when the appointment is confirmed. Nothing else.

**D3. Credit is still consumed when it exists.** The admin booking is not free by
default: when the person has no quota left and holds an available credit at the
employer, that credit pays for the session, exactly as it would in the app. The
appointment is free only when there is nothing to pay with.

**D4. No separate "courtesy" marking for now.** The audit proposed recording the funding
source (`quota | credit | courtesy`) so reports can tell the cases apart. That is a
reporting need, not a booking rule, and it is deferred until a report asks for it
(see appointments-01 on persisting how an appointment was paid).

## Consequences

- `CreateAppointment` keeps its current behaviour; the docblock and this record are the
  statement of intent the audit found missing.
- Reports that derive quota usage from the count of appointments can show a person over
  quota after an admin booking. That is correct: the admin booked beyond the quota.
- Cancelling an admin-booked session that consumed no credit has no credit to refund.
- A test covers the free case: user with no quota and no credit, appointment created,
  no credit touched. The existing tests cover the quota and credit cases.
- Moving the admin page onto the same domain action as the app (arq-10, appointments-05)
  remains an architectural choice that must preserve D1 to D3 if ever made.
