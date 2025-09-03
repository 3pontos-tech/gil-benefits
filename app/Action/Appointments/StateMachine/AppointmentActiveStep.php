<?php

namespace App\Action\Appointments\StateMachine;

use App\Enums\AppointmentStatus;
use Filament\Notifications\Notification;

class AppointmentActiveStep extends AbstractAppointmentStep
{

    public function processStep(): void
    {
        $this->appointment->update(['status' => AppointmentStatus::Completed]);
    }

    public function notify(): void
    {
        Notification::make()
            ->title('Appointment Finished!')
            ->body('Your appointment has been completed. Please check your dashboard for details.')
            ->success()
            ->sendToDatabase($this->appointment->user)
            ->send();

        // todo: send mail with date/time to user
    }
}