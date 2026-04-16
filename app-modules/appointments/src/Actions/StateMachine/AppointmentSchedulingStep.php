<?php

namespace TresPontosTech\Appointments\Actions\StateMachine;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentBooked;
use TresPontosTech\Appointments\Mail\AppointmentScheduledMail;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\CreateAppointmentCalendarEventJob;

class AppointmentSchedulingStep extends AbstractAppointmentStep
{
    public function processStep(): void
    {
        $this->appointment->update(['status' => AppointmentStatus::Active]);

        event(new AppointmentBooked($this->appointment));

        $this->dispatchCalendarEvent();
    }

    private function dispatchCalendarEvent(): void
    {
        $this->appointment->loadMissing('consultant');

        $consultant = $this->appointment->consultant;

        if (blank($consultant) || blank($consultant->email)) {
            return;
        }

        dispatch(new CreateAppointmentCalendarEventJob($this->appointment));
    }

    public function notify(): void
    {
        Notification::make()
            ->title(__('appointments::resources.appointments.notifications.scheduled.title'))
            ->body(__('appointments::resources.appointments.notifications.scheduled.body'))
            ->success()
            ->sendToDatabase($this->appointment->user)
            ->send();

        $this->appointment->loadMissing('consultant');

        $consultant = $this->appointment->consultant;

        if (blank($consultant) || blank($consultant->email)) {
            return;
        }

        Mail::to($consultant->email)->send(new AppointmentScheduledMail($this->appointment));
    }
}
