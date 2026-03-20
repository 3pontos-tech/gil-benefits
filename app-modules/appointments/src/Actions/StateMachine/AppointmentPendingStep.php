<?php

namespace TresPontosTech\Appointments\Actions\StateMachine;

use Filament\Notifications\Notification;
use TresPontosTech\Appointments\Enums\AppointmentStatus;

class AppointmentPendingStep extends AbstractAppointmentStep
{
    public function processStep(): void
    {
        $this->appointment->update(['status' => AppointmentStatus::Scheduling]);
    }

    public function notify(): void
    {
        Notification::make()
            ->title(__('appointments::resources.appointments.notifications.pending.title'))
            ->body(__('appointments::resources.appointments.notifications.pending.body'))
            ->success()
            ->sendToDatabase($this->appointment->user)
            ->send();

        //        Notification::make()
        //            ->title('New Appointment')
        //            ->body('A new appointment has been created. Please check and confirm.')
        //            ->success()
        //            ->sendToDatabase($this->appointment->consultant)
        //            ->send();
    }
}
