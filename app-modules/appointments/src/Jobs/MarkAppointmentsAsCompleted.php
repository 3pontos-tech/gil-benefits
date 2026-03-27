<?php

namespace TresPontosTech\Appointments\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

class MarkAppointmentsAsCompleted implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $count = 0;

        Appointment::query()
            ->where('status', AppointmentStatus::Active)
            ->where('appointment_at', '<', now())
            ->whereNotNull('consultant_id')
            ->chunkById(100, function ($appointments) use (&$count): void {
                foreach ($appointments as $appointment) {
                    $appointment->status->currentStep($appointment)->processStep();
                    ++$count;
                }
            });

        Log::info("appointments:mark-completed: {$count} appointment(s) marked as completed.");
    }

    public function failed(Throwable $exception): void
    {
        Log::error('MarkAppointmentsAsCompleted job failed.', [
            'error' => $exception->getMessage(),
        ]);
    }
}
