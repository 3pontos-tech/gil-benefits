<?php

namespace App\Action\Appointments\StateMachine;

use App\Enums\AppointmentStatus;
use Filament\Notifications\Notification;

class AppointmentDoneStep extends AbstractAppointmentStep
{

    public function processStep(): void
    {
    }

    public function notify(): void
    {
    }
}