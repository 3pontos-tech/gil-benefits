<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationGoogleCalendar;

use Throwable;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\DeleteCalendarEventAction;
use TresPontosTech\IntegrationGoogleCalendar\Actions\UpdateCalendarEventTimeAction;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\CreateAppointmentCalendarEventJob;

/**
 * Reflects an appointment's consultant/time changes onto Google Calendar.
 *
 * Every operation is best-effort: failures are reported and turn the return value to false —
 * they never throw — so the caller can tell the user the calendar is out of sync without
 * aborting the edit. The underlying actions are resolved lazily so a no-op edit never builds
 * the API client (which validates credentials on construction).
 */
final class AppointmentCalendarSynchronizer
{
    /**
     * Remove the appointment's event from the given consultant's calendar (no-op if none).
     */
    public function removeFrom(Appointment $appointment, ?Consultant $consultant): bool
    {
        if (blank($appointment->google_event_id) || ! $consultant instanceof Consultant || blank($consultant->email)) {
            return true;
        }

        return $this->attempt(fn () => resolve(DeleteCalendarEventAction::class)->handle($appointment, $consultant->email));
    }

    /**
     * Create the event on the appointment's current consultant, unless it already has one.
     */
    public function placeForCurrentConsultant(Appointment $appointment): bool
    {
        if (filled($appointment->google_event_id) || ! $this->hasConsultantEmail($appointment)) {
            return true;
        }

        return $this->attempt(fn () => dispatch_sync(new CreateAppointmentCalendarEventJob($appointment)));
    }

    /**
     * Move the event to the appointment's new time, keeping its Meet link (or create if missing).
     */
    public function reschedule(Appointment $appointment): bool
    {
        if (! $this->hasConsultantEmail($appointment)) {
            return true;
        }

        return $this->attempt(filled($appointment->google_event_id)
            ? fn () => resolve(UpdateCalendarEventTimeAction::class)->handle($appointment)
            : fn () => dispatch_sync(new CreateAppointmentCalendarEventJob($appointment)));
    }

    private function hasConsultantEmail(Appointment $appointment): bool
    {
        $consultant = $appointment->loadMissing('consultant')->consultant;

        return $consultant instanceof Consultant && filled($consultant->email);
    }

    private function attempt(callable $operation): bool
    {
        try {
            $operation();

            return true;
        } catch (Throwable $throwable) {
            report($throwable);

            return false;
        }
    }
}
