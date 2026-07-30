<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\SyncConsultantCalendarAction;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\Support\LogSanitizer;

class SyncConsultantCalendarJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @var array<int, int>
     */
    public array $backoff = [10, 60, 300];

    public function __construct(
        public Consultant $consultant,
        public bool $forceFullSync = false,
    ) {}

    public function handle(SyncConsultantCalendarAction $action): void
    {
        try {
            $action->handle($this->consultant, $this->forceFullSync);
        } catch (GoogleCalendarApiException $googleCalendarApiException) {
            if (! $googleCalendarApiException->retryable) {
                Log::warning('Google Calendar sync skipped for consultant', [
                    'consultant_id' => $this->consultant->id,
                    'force_full_sync' => $this->forceFullSync,
                    'reason' => LogSanitizer::sanitize($googleCalendarApiException->getMessage()),
                    'skipped_at' => Date::now()->toIso8601String(),
                ]);

                return;
            }

            throw $googleCalendarApiException;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Google Calendar sync failed for consultant', [
            'consultant_id' => $this->consultant->id,
            'force_full_sync' => $this->forceFullSync,
            'reason' => LogSanitizer::sanitize($exception->getMessage()),
            'failed_at' => Date::now()->toIso8601String(),
        ]);
    }
}
