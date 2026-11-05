---
type: adr
title: "An appointment is cancelled, never deleted"
module: appointments
status: accepted
date: 2026-11-05
supersedes: none
affects:
  - appointments
  - credits
  - panel-admin
---

# ADR: An appointment is cancelled, never deleted

**Status:** accepted · **Date:** 2026-11-05 · **Decided by:** Richard (product/tech)

Closes audit findings appointments-04 and credits-v01, and the remaining part of
panel-admin-07 (bulk delete of appointments outside the state machine).

## Context

The admin panel offered "Delete" on the appointment edit page and "Delete selected" on
the list. Both were Filament's soft delete straight on the model: no domain action, no
event, no listener. Cancelling, on the other hand, goes through
`CancelAppointmentForUserAction`, fires `AppointmentCancelled`, and the credits module
returns the credit; the person and the consultant are notified and the Google Calendar
event is removed.

Deleting an appointment paid with a credit left that credit `in_use` forever, pointing at
a trashed row the admin list never shows. Deleting an appointment paid with quota gave the
quota back silently, with no status, actor, reason or notification, and left the calendar
event behind. The delete button was being used as "cancel without a trace".

Since 2026-11-03 (ADR on hard deletes) the database refuses a hard delete of an
appointment that still has a credit attached. Nothing refused the soft delete.

## Decisions

**D1. There is no delete for appointments.** No panel offers a delete, bulk delete, force
delete or restore on appointments. The only way to take an appointment off the agenda is
Cancel, with the actor recorded. An appointment created by mistake is cancelled too; a
cancellation by the admin carries no penalty for the employee.

**D2. No guard on the Eloquent lifecycle.** A listener on the model's `deleting` event
that throws or vetoes was considered and rejected: it fights the framework (a refused
`forceDelete()` leaves the model in a state where the next `delete()` hard deletes) and
protects a path that, by D1, does not exist. The enforcement is an architecture test over
the appointment resources of every panel, plus this record.

**D3. Appointments already in the trash stay there.** None holds a credit. Restoring them
would rewrite history that the admin chose to hide.

**D4. `SoftDeletes` leaves the model later.** Removing the trait from `Appointment` is part
of the appointment lifecycle epic. Until then `delete()` exists on the model and is simply
never called by application code.

## Consequences

- `EditAppointment` loses `DeleteAction`; `AppointmentsTable` loses its bulk action group.
- `tests/Architecture/AppointmentDeletionTest.php` fails if any appointment resource
  imports a Filament delete, bulk delete or force delete action.
- A test on the admin pages asserts the actions are absent; `AppointmentTransitionActionsTest`
  keeps covering Cancel.
- Appointment histories, feedbacks and records are never lost through the admin.
