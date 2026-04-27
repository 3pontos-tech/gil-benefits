<?php

namespace TresPontosTech\Appointments\Actions\StateMachine;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentBooked;
use TresPontosTech\Appointments\Mail\AppointmentScheduledMail;

class AppointmentPendingStep extends AbstractAppointmentStep
{
    public function targetStatus(): AppointmentStatus
    {
        return AppointmentStatus::Active;
    }

    protected function guard(): bool
    {
        return filled($this->appointment->consultant_id);
    }

    public function processStep(): void
    {
        $this->appointment->update(['status' => $this->targetStatus()]);

        event(new AppointmentBooked($this->appointment));
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

        Mail::to($consultant->email)->queue(new AppointmentScheduledMail($this->appointment));
    }
}
