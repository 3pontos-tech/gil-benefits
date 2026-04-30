<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Transitions;

final class CancelledLateTransition extends AbstractAppointmentTransition
{
    public function choices(): array
    {
        return [];
    }

    public function canChange(): bool
    {
        return false;
    }

    public function validate(TransitionData $data): void {}

    public function processStep(TransitionData $data): void {}

    public function notify(TransitionData $data): void {}
}
