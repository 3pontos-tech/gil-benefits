<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationGoogleCalendar\Listeners;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;

final class RemoveCalendarEventOnAppointmentCancelled implements ShouldHandleEventsAfterCommit
{
    public function handle(AppointmentCancelled $event): void
    {
        if (blank($event->appointment->google_event_id)) {
            return;
        }

        dispatch(new DeleteAppointmentCalendarEventJob($event->appointment));
    }
}
