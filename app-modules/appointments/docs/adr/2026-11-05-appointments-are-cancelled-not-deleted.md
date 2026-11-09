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

Closes audit findings appointments-04 and panel-admin-07, and the part of credits-v01
that concerns deletion (see D5 for what stays out).

## Context

The admin panel offered "Delete" on the appointment edit page and "Delete selected" on
the list. Both were Filament's soft delete straight on the model: no domain action, no
event, no listener.

Cancelling goes through the state machine. The employee cancels through
`CancelAppointmentForUserAction`; the admin cancels from the view page through
`current_transition->handle()` with `CancellationActor::Admin`. Both paths run the same
cancel step: `AppointmentCancelled` is fired, the credits module returns the credit or the
quota is given back, the consultant's slot is released in Zap, the Google Calendar event
is removed, and the employee, the consultant and the admins are notified.

Deleting skipped all of that. An appointment paid with a credit left the credit `in_use`
forever, pointing at a trashed row the admin list never shows. An appointment paid with
quota gave the quota back silently, with no status, actor, reason or notification. In both
cases the Zap slot stayed blocked (the `APPOINTMENT` row remained and that type prevents
overlap) and the calendar event stayed behind. The delete button was being used as
"cancel without a trace".

Since 2026-11-03 (ADR on hard deletes) the database refuses a hard delete of an
appointment that still has a credit attached. Nothing refused the soft delete.

## Decisions

**D1. There is no delete for appointments.** No panel offers a delete, bulk delete, force
delete or restore on appointments. The only way to take an appointment off the agenda is
Cancel, with the actor recorded. An appointment created by mistake is cancelled too; a
cancellation by the admin carries no penalty for the employee.

**D2. No guard on the Eloquent lifecycle.** A listener on the model's `deleting` event was
considered and rejected. A listener that throws leaves the model dirty: `forceDelete()`
sets `forceDeleting` before calling `delete()` and only resets it after a successful
delete, so the next `delete()` on the same instance hard deletes. A listener that vetoes
by returning `false` keeps the flag clean, but fails silently and protects a path that,
by D1, no application code uses. The enforcement is an architecture test over the
appointment resources of every panel, plus this record.

**D3. Appointments already in the trash stay there.** Restoring them would rewrite
history that the admin chose to hide.

**D4. `SoftDeletes` leaves the model only after deletion is blocked at the database.**
With the trait gone, `delete()` becomes a hard delete. An appointment paid with quota has
no credit, so nothing would refuse it today: `appointment_records` and
`appointment_feedbacks` cascade, and `appointment_histories.appointment_id` has no
foreign key at all, so the history would be orphaned. The trait is removed in the
appointment lifecycle work, after those tables are on `restrict` (or an equivalent guard
exists). Until then `delete()` exists on the model and is simply never called by
application code.

**D5. No `AppointmentPolicy::delete`.** credits-v01 proposed one. There is no
`AppointmentPolicy` today, and creating one means defining every ability for the three
panels. With no delete to deny, the policy would exist only to say "no" to something
nothing calls. It is out of scope here; the architecture test takes its place.

## Consequences

- `EditAppointment` loses `DeleteAction`; `AppointmentsTable` loses its bulk action group.
- `TagsTable` loses `ForceDeleteBulkAction` and `RestoreBulkAction`, the other half of
  panel-admin-07 that #331 had deferred: `Spatie\Tags\Tag` has no `SoftDeletes`, so those
  actions never made sense there.
- `tests/Architecture/AppointmentDeletionTest.php` fails if any appointment resource
  imports a Filament delete, bulk delete, force delete or restore action. It covers the
  `Resources\Appointments` namespaces only; widgets outside them are not checked.
- A test on the admin pages asserts the actions are absent; `AppointmentTransitionActionsTest`
  keeps covering Cancel.
- An appointment created by mistake is now cancelled by the admin. That notifies the
  employee, the consultant and the admins, and the appointment counts as cancelled in the
  company metrics (`GetAppointmentStats` does not split by actor). Delete used to hide it.
- Appointment histories, feedbacks and records are never lost through the admin.
