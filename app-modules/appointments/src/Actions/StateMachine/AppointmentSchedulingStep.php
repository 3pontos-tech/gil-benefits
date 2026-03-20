<?php

namespace TresPontosTech\Appointments\Actions\StateMachine;

use Filament\Notifications\Notification;
use TresPontosTech\Appointments\Enums\AppointmentStatus;

class AppointmentSchedulingStep extends AbstractAppointmentStep
{
    public function processStep(): void
    {
        $this->appointment->update(['status' => AppointmentStatus::Active]);
    }

    public function notify(): void
    {
        Notification::make()
            ->title(__('appointments::resources.appointments.notifications.scheduled.title'))
            ->body(__('appointments::resources.appointments.notifications.scheduled.body'))
            ->success()
            ->sendToDatabase($this->appointment->user)
            ->send();

        // todo: send mail with date/time to user
    }
}
