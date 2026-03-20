<?php

namespace TresPontosTech\Appointments\Actions\StateMachine;

use Filament\Notifications\Notification;
use TresPontosTech\Appointments\Enums\AppointmentStatus;

class AppointmentActiveStep extends AbstractAppointmentStep
{
    public function processStep(): void
    {
        $this->appointment->update(['status' => AppointmentStatus::Completed]);
    }

    public function notify(): void
    {
        Notification::make()
            ->title(__('appointments::resources.appointments.notifications.completed.title'))
            ->body(__('appointments::resources.appointments.notifications.completed.body'))
            ->success()
            ->sendToDatabase($this->appointment->user)
            ->send();

        // todo: send mail with date/time to user
    }
}
