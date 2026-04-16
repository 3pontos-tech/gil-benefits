<?php

namespace TresPontosTech\Appointments\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

class MarkAppointmentsAsCompleted implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        Appointment::query()
            ->where('status', AppointmentStatus::Active)
            ->where('appointment_at', '<', now())
            ->whereNotNull('consultant_id')
            ->chunkById(100, function ($appointments): void {
                foreach ($appointments as $appointment) {
                    $appointment->status->currentStep($appointment)->handle();
                }
            });
    }
}
