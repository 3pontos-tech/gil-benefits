<?php

namespace TresPontosTech\Appointments\Actions;

use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

readonly class AssignConsultantAction
{
    public function handle(Appointment $appointment): void
    {
        if (blank($appointment->consultant_id)) {
            return;
        }

        DB::transaction(function () use ($appointment): void {
            Schedule::query()
                ->where('schedule_type', ScheduleTypes::APPOINTMENT)
                ->whereJsonContains('metadata->appointment_id', $appointment->id)
                ->delete();

            $consultant = $appointment->consultant;

            Schedule::query()
                ->where('schedulable_type', 'consultant')
                ->where('schedulable_id', $consultant->getKey())
                ->where('start_date', '<=', $appointment->appointment_at->toDateString())
                ->where('end_date', '>=', $appointment->appointment_at->toDateString())
                ->lockForUpdate()
                ->get();

            $isAvailable = $consultant->isBookableAtTime(
                $appointment->appointment_at->format('Y-m-d'),
                $appointment->appointment_at->format('H:i'),
                $appointment->appointment_at->copy()->addHour()->format('H:i'),
            );

            throw_unless($isAvailable, SlotUnavailableException::class);

            Zap::for($consultant)
                ->named(sprintf('Appointment #%s - %s', $appointment->id, $appointment->user->name))
                ->appointment()
                ->from($appointment->appointment_at->toDateString())
                ->to($appointment->appointment_at->copy()->addDay()->toDateString())
                ->addPeriod(
                    $appointment->appointment_at->format('H:i'),
                    $appointment->appointment_at->copy()->addHour()->format('H:i'),
                )
                ->withMetadata(['appointment_id' => $appointment->id])
                ->save();
        });
    }
}
