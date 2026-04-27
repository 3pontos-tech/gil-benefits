<?php

namespace TresPontosTech\Appointments\Actions\StateMachine;

use TresPontosTech\Appointments\Actions\CancelAppointmentAction;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

abstract class AbstractAppointmentStep
{
    public function __construct(
        public Appointment $appointment,
    ) {}

    abstract public function targetStatus(): AppointmentStatus;

    protected function guard(): bool
    {
        return true;
    }

    public function handle(): void
    {
        if (! $this->appointment->status->canTransitionTo($this->targetStatus())) {
            throw new \LogicException(sprintf(
                'Cannot process step: transition from "%s" to "%s" is not allowed.',
                $this->appointment->status->value,
                $this->targetStatus()->value,
            ));
        }

        if (! $this->guard()) {
            throw new \LogicException(sprintf(
                'Cannot process step: business guard failed for status "%s".',
                $this->appointment->status->value,
            ));
        }

        $this->processStep();

        $this->notify();
    }

    abstract public function processStep(): void;

    abstract public function notify(): void;

    public function cancel(): void
    {
        resolve(CancelAppointmentAction::class)->handle($this->appointment);
    }
}
