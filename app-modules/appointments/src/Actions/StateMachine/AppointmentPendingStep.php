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
            ->title('Appointment under Scheduling')
            ->body('We found a match for your appointment. We will contact you soon.')
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
