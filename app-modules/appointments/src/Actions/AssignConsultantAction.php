<?php

namespace TresPontosTech\Appointments\Actions;

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

        Schedule::query()
            ->where('schedule_type', ScheduleTypes::APPOINTMENT)
            ->whereJsonContains('metadata->appointment_id', $appointment->id)
            ->delete();

        $consultant = $appointment->consultant;

        Zap::for($consultant)
            ->named(sprintf('Appointment #%d - %s', $appointment->id, $appointment->user->name))
            ->appointment()
            ->from($appointment->appointment_at->toDateString())
            ->to($appointment->appointment_at->copy()->addDay()->toDateString())
            ->addPeriod(
                $appointment->appointment_at->format('H:i'),
                $appointment->appointment_at->copy()->addHour()->format('H:i'),
            )
            ->withMetadata(['appointment_id' => $appointment->id])
            ->save();
    }
}
