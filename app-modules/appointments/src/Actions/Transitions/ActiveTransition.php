<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Transitions;

use App\Models\Users\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Actions\AppointmentHistory\StoreAppointmentHistoryAction;
use TresPontosTech\Appointments\DTO\StoreAppointmentHistoryDTO;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentCompleted;
use TresPontosTech\Appointments\Exceptions\MissingTransitionDataException;
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
            $data->noShow && blank($data->noShowMarkedBy),
            MissingTransitionDataException::class,
            'A user must be provided when marking an appointment as no-show.'
        );
    }

    public function processStep(TransitionData $data): void
    {
        if (filled($data->cancellationActor)) {
            $this->cancelProcessStep($data);

            return;
        }

        if ($data->noShow) {
            $this->noShowProcessStep($data);

            return;
        }

        $this->appointment->update(['status' => AppointmentStatus::Completed]);

        event(new AppointmentCreditUsed((string) $this->appointment->getKey()));
        event(new AppointmentCompleted($this->appointment));
    }

    /**
     * Marks the appointment as a no-show, applies the credit rule and writes the
     * audit trail. Runs inside the handle() DB transaction: the status update,
     * the credit update (synchronous listeners) and the history row all commit
     * or roll back together.
     *
     * Credit rule (Option A): a no-show consumes the credit exactly like a
     * completed appointment — the beneficiary booked the slot and did not
     * free it up in time, so it is billed the same way.
     */
    private function noShowProcessStep(TransitionData $data): void
    {
        $previousStatus = $this->appointment->status;

        $this->appointment->update(['status' => AppointmentStatus::NoShow]);

        $this->appointment->loadMissing('user');
        $this->appointment->user->forgetMonthlyAppointmentsLeftCache();

        $hasCreditInUse = $this->appointment->credit()
            ->where('status', UserCreditStatusEnum::InUse)
            ->exists();

        $creditImpact = 'none';

        if ($hasCreditInUse) {
            $creditImpact = 'consumed';
        }

        event(new AppointmentCreditUsed((string) $this->appointment->getKey()));

        /** @var User $markedBy */
        $markedBy = $data->noShowMarkedBy;

        resolve(StoreAppointmentHistoryAction::class)->execute(StoreAppointmentHistoryDTO::make([
            'appointment_id' => (string) $this->appointment->getKey(),
            'actor_id' => (string) $markedBy->getKey(),
            'actor_type' => AppointmentHistoryActor::Consultant->value,
            'action_type' => AppointmentHistoryActionType::NoShowMarked->value,
            'old_values' => ['status' => $previousStatus->value],
            'new_values' => [
                'status' => AppointmentStatus::NoShow->value,
                'credit_impact' => $creditImpact,
            ],
        ]));
    }

    public function notify(TransitionData $data): void
    {
        if (filled($data->cancellationActor)) {
            $this->cancelNotify($data);

            return;
        }

        if ($data->noShow) {
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
