<?php

namespace App\Action\Appointments\StateMachine;

use App\Enums\AppointmentStatus;
use Filament\Notifications\Notification;

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

        Notification::make()
            ->title('New Appointment')
            ->body('A new appointment has been created. Please check and confirm.')
            ->success()
            ->sendToDatabase($this->appointment->consultant)
            ->send();
    }
}
