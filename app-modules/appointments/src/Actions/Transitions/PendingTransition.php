<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Transitions;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentBooked;
use TresPontosTech\Appointments\Exceptions\MissingTransitionDataException;
use TresPontosTech\Appointments\Mail\AppointmentScheduledMail;

final class PendingTransition extends AbstractAppointmentTransition
{
    public function choices(): array
    {
        return [AppointmentStatus::Active, AppointmentStatus::Cancelled, AppointmentStatus::CancelledLate];
    }

    public function canChange(): bool
    {
        return true;
    }

    public function validate(TransitionData $data): void
    {
        if (filled($data->cancellationActor)) {
            return;
        }

        if (blank($this->appointment->consultant_id)) {
            throw new MissingTransitionDataException('A consultant must be assigned before confirming the appointment.');
        }
    }

    public function processStep(TransitionData $data): void
    {
        if (filled($data->cancellationActor)) {
            $this->cancelProcessStep($data);

            return;
        }

        $this->appointment->update(['status' => AppointmentStatus::Active]);

        event(new AppointmentBooked($this->appointment));
    }

    public function notify(TransitionData $data): void
    {
        if (filled($data->cancellationActor)) {
            $this->cancelNotify($data);

            return;
        }

        Notification::make()
            ->title(__('appointments::resources.appointments.notifications.scheduled.title'))
            ->body(__('appointments::resources.appointments.notifications.scheduled.body'))
            ->success()
            ->sendToDatabase($this->appointment->user)
            ->send();

        $this->appointment->loadMissing('consultant');
        $consultant = $this->appointment->consultant;

        if (blank($consultant) || blank($consultant->email)) {
            return;
        }

        Mail::to($consultant->email)->queue(new AppointmentScheduledMail($this->appointment));
    }
}
