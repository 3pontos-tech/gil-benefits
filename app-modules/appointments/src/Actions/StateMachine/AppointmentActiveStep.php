<?php

namespace TresPontosTech\Appointments\Actions\StateMachine;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Mail\AppointmentCompletedMail;

class AppointmentActiveStep extends AbstractAppointmentStep
{
    public function processStep(): void
    {
        $this->appointment->update(['status' => AppointmentStatus::Completed]);
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

        Mail::to($this->appointment->user->email)->send(new AppointmentCompletedMail($this->appointment));
    }
}
