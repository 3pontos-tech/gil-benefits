<?php

namespace App\Action\Appointments\StateMachine;

use App\Enums\AppointmentStatus;
use Filament\Notifications\Notification;

class AppointmentSchedulingStep extends AbstractAppointmentStep
{
    public function processStep(): void
    {
        $this->appointment->update(['status' => AppointmentStatus::Active]);
    }

    public function notify(): void
    {
        Notification::make()
            ->title('Appointment Scheduled!')
            ->body('Your appointment has been scheduled. Please check your dashboard for details.')
            ->success()
            ->sendToDatabase($this->appointment->user)
            ->send();

        // todo: send mail with date/time to user
    }
}
