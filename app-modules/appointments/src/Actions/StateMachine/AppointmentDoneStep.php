<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\StateMachine;

class AppointmentDoneStep extends AbstractAppointmentStep
{
    public function processStep(): void {}

    public function notify(): void {}
}
