<?php

namespace TresPontosTech\Appointments\Actions\StateMachine;

use Filament\Notifications\Notification;
use TresPontosTech\Appointments\Enums\AppointmentStatus;

class AppointmentDraftStep extends AbstractAppointmentStep
{
    public function processStep(): void
    {
        $this->appointment->update(['status' => AppointmentStatus::Pending]);
    }

    public function notify(): void
    {
        Notification::make()
            ->title(__('appointments::resources.appointments.notifications.drafted.title'))
            ->body(__('appointments::resources.appointments.notifications.drafted.body'))
            ->success()
            ->sendToDatabase($this->appointment->user)
            ->send();

        // TODO: mailing later
    }
}
