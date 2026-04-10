<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Actions\CreateCalendarEventAction;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\Support\LogSanitizer;

class CreateAppointmentCalendarEventJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        public Appointment $appointment,
    ) {}

    public function handle(CreateCalendarEventAction $action): void
    {
        try {
            $action->handle($this->appointment);
        } catch (GoogleCalendarApiException $exception) {
            if (! $exception->retryable) {
                Log::warning('Google Calendar event creation skipped', [
                    'appointment_id' => $this->appointment->id,
                    'exception' => $exception::class,
                    'error_code' => $exception->getCode(),
                    'retryable' => false,
                    'reason' => LogSanitizer::sanitize($exception->getMessage()),
                ]);

                return;
            }

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Google Calendar event creation failed', [
            'appointment_id' => $this->appointment->id,
            'exception' => $exception::class,
            'error_code' => $exception->getCode(),
            'reason' => LogSanitizer::sanitize($exception->getMessage()),
        ]);
    }
}
