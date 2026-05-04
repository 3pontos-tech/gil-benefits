<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Transitions;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentCompleted;
use TresPontosTech\Appointments\Mail\AppointmentCompletedMail;

final class ActiveTransition extends AbstractAppointmentTransition
{
    public function choices(): array
    {
        return [AppointmentStatus::Completed, AppointmentStatus::Cancelled, AppointmentStatus::CancelledLate];
    }

    public function canChange(): bool
    {
        return true;
    }

    public function validate(TransitionData $data): void {}

    public function processStep(TransitionData $data): void
    {
        if (filled($data->cancellationActor)) {
            $this->cancelProcessStep($data);

            return;
        }

        $this->appointment->update(['status' => AppointmentStatus::Completed]);

        event(new AppointmentCompleted($this->appointment));
    }

    public function notify(TransitionData $data): void
    {
        if (filled($data->cancellationActor)) {
            $this->cancelNotify($data);

            return;
        }

        $this->appointment->loadMissing(['user', 'consultant']);

        Notification::make()
            ->title(__('appointments::resources.appointments.notifications.completed.title'))
            ->body(__('appointments::resources.appointments.notifications.completed.body'))
            ->success()
            ->sendToDatabase($this->appointment->user)
            ->send();

        Mail::to($this->appointment->user->email)->queue(new AppointmentCompletedMail($this->appointment));
    }
}
