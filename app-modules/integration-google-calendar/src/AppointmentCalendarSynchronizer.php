<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationGoogleCalendar;

use Illuminate\Support\Facades\Log;
use Throwable;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\DeleteCalendarEventAction;
use TresPontosTech\IntegrationGoogleCalendar\Actions\UpdateCalendarEventTimeAction;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\CreateAppointmentCalendarEventJob;
use TresPontosTech\IntegrationGoogleCalendar\Support\LogSanitizer;

/**
 * Reflects an appointment's consultant/time changes onto Google Calendar.
 *
 * Every operation is best-effort: failures are logged or reported and turn the return value to false —
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

        return $this->create($appointment);
    }

    /**
     * Move the event to the appointment's new time, keeping its Meet link (or create if missing).
     */
    public function reschedule(Appointment $appointment): bool
    {
        if (! $this->hasConsultantEmail($appointment)) {
            return true;
        }

        return filled($appointment->google_event_id)
            ? $this->attempt(fn () => resolve(UpdateCalendarEventTimeAction::class)->handle($appointment))
            : $this->create($appointment);
    }

    /**
     * Create the event and confirm it landed.
     *
     * The creation job deliberately swallows the failures it cannot retry, so "nothing was thrown"
     * does not mean the calendar was updated. The outcome is read back from the record instead:
     * without an event id there is no event, and the caller has to tell the user.
     */
    private function create(Appointment $appointment): bool
    {
        return $this->attempt(fn () => dispatch_sync(new CreateAppointmentCalendarEventJob($appointment)))
            && filled($appointment->refresh()->google_event_id);
    }

    private function hasConsultantEmail(Appointment $appointment): bool
    {
        $consultant = $appointment->loadMissing('consultant')->consultant;

        return $consultant instanceof Consultant && filled($consultant->email);
    }

    /**
     * Run a calendar operation, turning any failure into a false return.
     *
     * A non-retryable failure is permanent Google-side state (Calendar disabled for the consultant,
     * bad credentials): the caller still warns the user the calendar is out of sync, but there is
     * nothing to fix on our side, so it is logged instead of reported.
     */
    private function attempt(callable $operation): bool
    {
        try {
            $operation();

            return true;
        } catch (GoogleCalendarApiException $googleCalendarApiException) {
            if ($googleCalendarApiException->retryable) {
                report($googleCalendarApiException);

                return false;
            }

            Log::warning('Google Calendar sync skipped, operation is not retryable', [
                'error_code' => $googleCalendarApiException->getCode(),
                'reason' => LogSanitizer::sanitize($googleCalendarApiException->getMessage()),
            ]);

            return false;
        } catch (Throwable $throwable) {
            report($throwable);

            return false;
        }
    }
}
