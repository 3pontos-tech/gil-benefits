<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Transitions;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Actions\AppointmentHistory\StoreAppointmentHistoryAction;
use TresPontosTech\Appointments\DTO\StoreAppointmentHistoryDTO;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CreditImpact;
use TresPontosTech\Appointments\Events\AppointmentCompleted;
use TresPontosTech\Appointments\Events\AppointmentNoShow;
use TresPontosTech\Appointments\Exceptions\InvalidTransitionException;
use TresPontosTech\Appointments\Mail\AppointmentCompletedMail;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Events\Credit\AppointmentCreditUsed;

final class ActiveTransition extends AbstractAppointmentTransition
{
    public function choices(): array
    {
        return [
            AppointmentStatus::Completed,
            AppointmentStatus::Cancelled,
            AppointmentStatus::CancelledLate,
            AppointmentStatus::NoShow,
        ];
    }

    public function canChange(): bool
    {
        return true;
    }

    public function validate(TransitionData $data): void
    {
        throw_if(
            filled($data->noShowMarkedBy) && filled($data->cancellationActor),
            InvalidTransitionException::class,
            'An appointment cannot be marked as no-show and cancelled at the same time.'
        );
    }

    public function processStep(TransitionData $data): void
    {
        if (filled($data->cancellationActor)) {
            $this->cancelProcessStep($data);

            return;
        }

        if (filled($data->noShowMarkedBy)) {
            $this->noShowProcessStep($data);

            return;
        }

        $this->appointment->update(['status' => AppointmentStatus::Completed]);

        event(new AppointmentCreditUsed((string) $this->appointment->getKey()));
        event(new AppointmentCompleted($this->appointment));
    }

    private function noShowProcessStep(TransitionData $data): void
    {
        $previousStatus = $this->appointment->status;

        $this->appointment->update(['status' => AppointmentStatus::NoShow]);

        $this->appointment->loadMissing('user');
        DB::afterCommit(fn () => $this->appointment->user->forgetMonthlyAppointmentsLeftCache());

        event(new AppointmentCreditUsed((string) $this->appointment->getKey()));

        $creditImpact = $this->appointment->credit()
            ->where('status', UserCreditStatusEnum::Used)
            ->exists()
            ? CreditImpact::Consumed
            : CreditImpact::None;

        resolve(StoreAppointmentHistoryAction::class)->execute(StoreAppointmentHistoryDTO::make([
            'appointment_id' => (string) $this->appointment->getKey(),
            'actor_id' => (string) $data->noShowMarkedBy->getKey(),
            'actor_type' => AppointmentHistoryActor::Consultant->value,
            'action_type' => AppointmentHistoryActionType::NoShowMarked->value,
            'old_values' => ['status' => $previousStatus->value],
            'new_values' => [
                'status' => AppointmentStatus::NoShow->value,
                'credit_impact' => $creditImpact->value,
            ],
        ]));

        event(new AppointmentNoShow($this->appointment));
    }

    public function notify(TransitionData $data): void
    {
        if (filled($data->cancellationActor)) {
            $this->cancelNotify($data);

            return;
        }

        if (filled($data->noShowMarkedBy)) {
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
