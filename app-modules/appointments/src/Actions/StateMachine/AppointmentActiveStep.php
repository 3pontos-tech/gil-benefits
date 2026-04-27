<?php

namespace TresPontosTech\Appointments\Actions\StateMachine;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentCompleted;
use TresPontosTech\Appointments\Mail\AppointmentCompletedMail;

class AppointmentActiveStep extends AbstractAppointmentStep
{
    public function targetStatus(): AppointmentStatus
    {
        return AppointmentStatus::Completed;
    }

    public function processStep(): void
    {
        $this->appointment->update(['status' => $this->targetStatus()]);

        event(new AppointmentCompleted($this->appointment));
    }

    public function notify(): void
    {
        $this->appointment->loadMissing(['user', 'consultant']);

        Notification::make()
            ->title(__('appointments::resources.appointments.notifications.completed.title'))
            ->body(__('appointments::resources.appointments.notifications.completed.body'))
            ->success()
            ->sendToDatabase($this->appointment->user)
            ->send();

        Mail::to($this->appointment->user->email)->queue(new AppointmentCompletedMail($this->appointment));
    }
}
