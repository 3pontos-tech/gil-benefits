<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\Appointments\Actions\AppointmentHistory\StoreAppointmentHistoryAction;
use TresPontosTech\Appointments\DTO\StoreAppointmentHistoryDTO;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Mail\AppointmentConsultantUnassignedMail;
use TresPontosTech\Appointments\Mail\AppointmentScheduledMail;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\AppointmentCalendarSynchronizer;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

/**
 * Applies the domain side effects of editing an appointment's consultant and/or time: keeps the
 * internal agenda (Zap) blocked, notifies the consultants and, on an unavailable slot, reverts the
 * record and rethrows SlotUnavailableException (this panel has no database transaction to roll
 * back). The Google Calendar side is delegated to the synchronizer, whose best-effort result is
 * bubbled up so the caller can warn the user when the calendar ends up out of sync.
 */
final readonly class SyncAppointmentScheduleAction
{
    public function __construct(
        private AssignConsultantAction $assignConsultant,
        private AppointmentCalendarSynchronizer $calendar,
        private StoreAppointmentHistoryAction $storeAppointmentHistory,
    ) {}

    /**
     * @return bool false when a Google Calendar operation failed (the appointment was still saved).
     */
    public function handle(
        Appointment $appointment,
        ?string $previousConsultantId,
        ?CarbonInterface $previousAppointmentAt,
    ): bool {
        $consultantChanged = $appointment->consultant_id !== $previousConsultantId;
        $timeChanged = ! $previousAppointmentAt instanceof CarbonInterface || ! $appointment->appointment_at->equalTo($previousAppointmentAt);

        if (! $consultantChanged && ! $timeChanged) {
            return true;
        }

        if ($timeChanged) {
            $this->recordHistory($appointment, AppointmentHistoryActionType::ReScheduled);
        }

        if ($consultantChanged && blank($appointment->consultant_id)) {
            $this->recordHistory($appointment, AppointmentHistoryActionType::ConsultantLeft);

            return $this->unassign($appointment, $previousConsultantId);
        }

        if (blank($appointment->consultant_id)) {
            return true;
        }

        $this->blockAgenda($appointment, $previousConsultantId, $previousAppointmentAt);

        return $consultantChanged
            ? $this->reassign($appointment, $previousConsultantId)
            : $this->reschedule($appointment);
    }

    private function recordHistory(Appointment $appointment, AppointmentHistoryActionType $type): void
    {
        $this->storeAppointmentHistory->execute(StoreAppointmentHistoryDTO::make([
            'appointment_id' => $appointment->id,
            'admin_id' => auth()->user()->getKey(),
            'action_type' => $type->value,
            'old_values' => $appointment->getPrevious(),
            'new_values' => $appointment->getChanges(),
        ]));
    }

    private function blockAgenda(
        Appointment $appointment,
        ?string $previousConsultantId,
        ?CarbonInterface $previousAppointmentAt,
    ): void {
        try {
            $this->assignConsultant->handle($appointment);
        } catch (SlotUnavailableException $slotUnavailableException) {
            $appointment->update([
                'consultant_id' => $previousConsultantId,
                'appointment_at' => $previousAppointmentAt,
            ]);

            throw $slotUnavailableException;
        }
    }

    private function reassign(Appointment $appointment, ?string $previousConsultantId): bool
    {
        $this->recordHistory($appointment, blank($previousConsultantId)
            ? AppointmentHistoryActionType::ConsultantAssigned
            : AppointmentHistoryActionType::ConsultantChanged);

        $previousConsultant = $this->resolveConsultant($previousConsultantId);

        $synced = $this->calendar->removeFrom($appointment, $previousConsultant);

        if ($appointment->status !== AppointmentStatus::Active) {
            return $synced;
        }

        $this->notifyPreviousConsultant($appointment, $previousConsultant);

        $consultant = $appointment->loadMissing('consultant')->consultant;

        if (! $consultant instanceof Consultant || blank($consultant->email)) {
            return $synced;
        }

        $synced = $this->calendar->placeForCurrentConsultant($appointment) && $synced;

        Mail::to($consultant->email)->queue(new AppointmentScheduledMail($appointment->refresh()));

        return $synced;
    }

    private function reschedule(Appointment $appointment): bool
    {
        if ($appointment->status !== AppointmentStatus::Active) {
            return true;
        }

        return $this->calendar->reschedule($appointment);
    }

    private function unassign(Appointment $appointment, ?string $previousConsultantId): bool
    {
        $synced = $this->calendar->removeFrom($appointment, $this->resolveConsultant($previousConsultantId));

        Schedule::query()
            ->where('schedule_type', ScheduleTypes::APPOINTMENT)
            ->whereJsonContains('metadata->appointment_id', $appointment->id)
            ->delete();

        // An appointment cannot stay Active without a consultant (state machine invariant).
        if ($appointment->status === AppointmentStatus::Active) {
            $appointment->update(['status' => AppointmentStatus::Pending]);
        }

        return $synced;
    }

    private function notifyPreviousConsultant(Appointment $appointment, ?Consultant $previousConsultant): void
    {
        if (! $previousConsultant instanceof Consultant) {
            return;
        }

        Mail::to($previousConsultant->email)
            ->queue(new AppointmentConsultantUnassignedMail($appointment, $previousConsultant));
    }

    private function resolveConsultant(?string $consultantId): ?Consultant
    {
        if (blank($consultantId)) {
            return null;
        }

        $consultant = Consultant::query()->find($consultantId);

        return $consultant instanceof Consultant && filled($consultant->email) ? $consultant : null;
    }
}
