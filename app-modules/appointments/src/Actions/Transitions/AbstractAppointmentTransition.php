<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\Transitions;

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Exceptions\InvalidTransitionException;
use TresPontosTech\Appointments\Mail\AppointmentCancelledMail;
use TresPontosTech\Appointments\Mail\AppointmentUserCancelledLateMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Billing\Core\Actions\ResolveQuotaAllowance;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Events\Credit\AppointmentCreditReturned;
use TresPontosTech\Billing\Core\Events\Credit\AppointmentCreditUsed;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Billing\Core\Support\QuotaCycle;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\DeleteAppointmentCalendarEventJob;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

abstract class AbstractAppointmentTransition
{
    public function __construct(public Appointment $appointment) {}

    /** @return list<AppointmentStatus> */
    abstract public function choices(): array;

    abstract public function canChange(): bool;

    abstract public function validate(TransitionData $data): void;

    abstract public function processStep(TransitionData $data): void;

    abstract public function notify(TransitionData $data): void;

    public function handle(TransitionData $data): void
    {
        if (! $this->canChange()) {
            throw new InvalidTransitionException(
                sprintf('Status "%s" is terminal and cannot be transitioned.', $this->appointment->status->value)
            );
        }

        DB::transaction(function () use ($data): void {
            $this->validate($data);
            $this->processStep($data);
        });

        $this->notify($data);
    }

    protected function cancelProcessStep(TransitionData $data): void
    {
        throw_if($data->cancellationActor === CancellationActor::User && $this->appointment->appointment_at->isPast(), InvalidTransitionException::class, 'Cannot cancel a past appointment.');

        $targetStatus = AppointmentStatus::resolveCancellationStatus($this->appointment, $data->cancellationActor);

        $this->appointment->update([
            'status' => $targetStatus,
            'cancelled_by' => $data->cancelledBy?->getKey(),
            'cancellation_actor' => $data->cancellationActor,
        ]);

        $this->appointment->loadMissing('user');

        if ($this->appointment->status === AppointmentStatus::Cancelled) {
            $this->stampQuotaRefundIfCycleClosed();

            event(new AppointmentCreditReturned((string) $this->appointment->getKey()));
        } else {
            event(new AppointmentCreditUsed((string) $this->appointment->getKey()));
        }

        Schedule::query()
            ->where('schedule_type', ScheduleTypes::APPOINTMENT)
            ->whereJsonContains('metadata->appointment_id', $this->appointment->id)
            ->delete();

        if (filled($this->appointment->google_event_id)) {
            dispatch(new DeleteAppointmentCalendarEventJob($this->appointment));
        }

        event(new AppointmentCancelled($this->appointment));
    }

    /**
     * Carimba a devolução quando o ciclo que pagou esta consulta já fechou.
     *
     * Precisa rodar antes de `AppointmentCreditReturned`, porque o listener daquele
     * evento zera `user_credits.appointment_id` — e é esse vínculo que diz se a
     * consulta foi paga com crédito avulso. Depois dele a informação não existe mais
     * em lugar nenhum, já que pagar com cota não escreve linha em tabela alguma.
     *
     * Nada é carimbado quando o ciclo do débito ainda é o corrente: nesse caso o
     * próprio cancelamento tira a reserva da contagem e a cota volta sozinha.
     *
     * A allowance é resolvida pela empresa do próprio agendamento, não pelo tenant
     * ativo: cancelar pelo admin, por job ou por outra empresa do mesmo usuário leria
     * outra âncora e devolveria a consulta no ciclo errado.
     */
    private function stampQuotaRefundIfCycleClosed(): void
    {
        $paidWithCredit = UserCredit::query()
            ->where('appointment_id', $this->appointment->getKey())
            ->where('status', UserCreditStatusEnum::InUse)
            ->exists();

        if ($paidWithCredit || $this->appointment->created_at === null) {
            return;
        }

        $allowance = resolve(ResolveQuotaAllowance::class)->for($this->appointment->user, $this->appointment->company_id);

        if ($allowance->isEmpty()) {
            return;
        }

        $debitedCycle = QuotaCycle::forAnchor($allowance->anchor, $this->appointment->created_at);

        if (! $debitedCycle->hasClosed()) {
            return;
        }

        $this->appointment->forceFill(['quota_refunded_at' => now()])->save();
    }

    protected function cancelNotify(TransitionData $data): void
    {
        $this->appointment->loadMissing(['user', 'consultant']);

        $isLate = $this->appointment->status === AppointmentStatus::CancelledLate;
        $notificationKey = $isLate ? 'user_cancelled_late' : 'cancelled';

        Notification::make()
            ->title(__(sprintf('appointments::resources.appointments.notifications.%s.title', $notificationKey)))
            ->body(__(
                sprintf('appointments::resources.appointments.notifications.%s.body', $notificationKey),
                ['hours' => Appointment::CANCELLATION_WINDOW_HOURS]
            ))
            ->warning()
            ->sendToDatabase($this->appointment->user)
            ->send();

        if (filled($this->appointment->consultant)) {
            $actorKey = $data->cancellationActor === CancellationActor::Admin ? 'cancelled_by_admin' : 'cancelled_by_user';

            Notification::make()
                ->title(__('appointments::resources.appointments.notifications.cancelled.title'))
                ->body(__(
                    sprintf('appointments::resources.appointments.notifications.%s.body', $actorKey),
                    ['name' => $this->appointment->user->name]
                ))
                ->warning()
                ->sendToDatabase($this->appointment->consultant->user);
        }

        $mail = $isLate
            ? new AppointmentUserCancelledLateMail($this->appointment)
            : new AppointmentCancelledMail($this->appointment);

        Mail::to($this->appointment->user->email)->queue($mail);
    }
}
