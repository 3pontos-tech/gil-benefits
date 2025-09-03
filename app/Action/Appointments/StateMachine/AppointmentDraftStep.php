<?php

namespace App\Action\Appointments\StateMachine;

use App\Enums\AppointmentStatus;
use Filament\Notifications\Notification;

class AppointmentDraftStep extends AbstractAppointmentStep
{

    public function processStep(): void
    {
        $this->appointment->update(['status' => AppointmentStatus::Pending]);
    }

    public function notify(): void
    {
        Notification::make()
            ->title('Appointment Drafted')
            ->body('Your appointment has been drafted. Soon we will contact you to confirm your appointment.')
            ->success()
            ->sendToDatabase($this->appointment->user)
            ->send();

        // TODO: mailing later
    }
}