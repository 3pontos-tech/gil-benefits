<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Transitions;

use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentBooked;
use TresPontosTech\Appointments\Exceptions\InvalidTransitionException;
use TresPontosTech\Appointments\Exceptions\MissingTransitionDataException;
use TresPontosTech\Appointments\Mail\AppointmentScheduledMail;
use TresPontosTech\Appointments\Notifications\AppointmentConfirmedNotification;
use TresPontosTech\Appointments\Support\AppointmentReminder;

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
        throw_if(filled($data->noShowMarkedBy), InvalidTransitionException::class, 'Only active appointments can be marked as no-show.');

        if (filled($data->cancellationActor)) {
            return;
        }

        throw_if(blank($this->appointment->consultant_id), MissingTransitionDataException::class, 'A consultant must be assigned before confirming the appointment.');
    }

    public function processStep(TransitionData $data): void
    {
        if (filled($data->cancellationActor)) {
            $this->cancelProcessStep($data);

            return;
        }

        // O aviso de confirmação acaba de dizer o horário: a menos de 1 h do encontro, ele é o lembrete.
        $this->appointment->forceFill([
            'status' => AppointmentStatus::Active,
            'reminder_sent_at' => AppointmentReminder::stampAfterTellingTheTime($this->appointment->appointment_at),
        ])->save();

        event(new AppointmentBooked($this->appointment));
    }

    public function notify(TransitionData $data): void
    {
        if (filled($data->cancellationActor)) {
            $this->cancelNotify($data);

            return;
        }

        $notification = new AppointmentConfirmedNotification($this->appointment);
        $this->appointment->user->notify($notification);
        $notification->flash();

        $this->appointment->loadMissing('consultant');
        $consultant = $this->appointment->consultant;

        if (blank($consultant) || blank($consultant->email)) {
            return;
        }

        Mail::to($consultant->email)->queue(new AppointmentScheduledMail($this->appointment));
    }
}
