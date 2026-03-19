<?php

namespace TresPontosTech\Appointments\Actions\StateMachine;

use Filament\Notifications\Notification;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

abstract class AbstractAppointmentStep
{
    public function __construct(
        public Appointment $appointment,
    ) {}

    public function handle(): void
    {
        $this->processStep();

        $this->notify();
    }

    abstract public function processStep(): void;

    abstract public function notify(): void;

    public function cancel(): void
    {
        Notification::make()
            ->title('Appointment Finished!')
            ->body('Your appointment has been completed. Please check your dashboard for details.')
            ->success()
            ->sendToDatabase($this->appointment->user)
            ->send();

        $this->appointment->update([
            'status' => AppointmentStatus::Cancelled,
        ]);
    }
}
