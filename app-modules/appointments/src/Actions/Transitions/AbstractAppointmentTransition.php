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
        if ($data->cancellationActor === CancellationActor::User && $this->appointment->appointment_at->isPast()) {
            throw new InvalidTransitionException('Cannot cancel a past appointment.');
        }

        $targetStatus = AppointmentStatus::resolveCancellationStatus($this->appointment, $data->cancellationActor);

        $this->appointment->update([
            'status' => $targetStatus,
            'cancelled_by' => $data->cancelledBy?->getKey(),
            'cancellation_actor' => $data->cancellationActor,
        ]);

        $this->appointment->loadMissing('user');
        $this->appointment->user->forgetMonthlyAppointmentsLeftCache();

        Schedule::query()
            ->where('schedule_type', ScheduleTypes::APPOINTMENT)
            ->whereJsonContains('metadata->appointment_id', $this->appointment->id)
            ->delete();

        if (filled($this->appointment->google_event_id)) {
            dispatch(new DeleteAppointmentCalendarEventJob($this->appointment));
        }

        event(new AppointmentCancelled($this->appointment));
    }

    protected function cancelNotify(TransitionData $data): void
    {
        $this->appointment->loadMissing(['user', 'consultant']);

        $isLate = $this->appointment->status === AppointmentStatus::CancelledLate;
        $notificationKey = $isLate ? 'user_cancelled_late' : 'cancelled';

        Notification::make()
            ->title(__(sprintf('appointments::resources.appointments.notifications.%s.title', $notificationKey)))
            ->body(__(sprintf('appointments::resources.appointments.notifications.%s.body', $notificationKey)))
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
