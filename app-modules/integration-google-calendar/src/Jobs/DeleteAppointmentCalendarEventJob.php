<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationGoogleCalendar\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\IntegrationGoogleCalendar\Actions\DeleteCalendarEventAction;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\Support\LogSanitizer;

class DeleteAppointmentCalendarEventJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        public Appointment $appointment,
    ) {}

    public function handle(DeleteCalendarEventAction $action): void
    {
        try {
            $action->handle($this->appointment);
        } catch (GoogleCalendarApiException $googleCalendarApiException) {
            if (! $googleCalendarApiException->retryable) {
                Log::warning('Google Calendar event deletion skipped', [
                    'appointment_id' => $this->appointment->id,
                    'exception' => $googleCalendarApiException::class,
                    'error_code' => $googleCalendarApiException->getCode(),
                    'retryable' => false,
                    'reason' => LogSanitizer::sanitize($googleCalendarApiException->getMessage()),
                ]);

                return;
            }

            throw $googleCalendarApiException;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Google Calendar event deletion failed', [
            'appointment_id' => $this->appointment->id,
            'exception' => $exception::class,
            'error_code' => $exception->getCode(),
            'reason' => LogSanitizer::sanitize($exception->getMessage()),
        ]);
    }
}
