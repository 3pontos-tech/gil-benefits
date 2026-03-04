<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\SyncConsultantCalendarAction;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;

class SyncConsultantCalendarJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(
        public Consultant $consultant,
    ) {}

    public function handle(SyncConsultantCalendarAction $action): void
    {
        try {
            $action->handle($this->consultant);
        } catch (GoogleCalendarApiException $e) {
            if (! $e->retryable) {
                Log::warning("Google Calendar sync skipped for consultant {$this->consultant->id}: {$e->getMessage()}");

                return;
            }

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Google Calendar sync failed for consultant {$this->consultant->id}", [
            'consultant_id' => $this->consultant->id,
            'consultant_email' => $this->consultant->email,
            'error' => $exception->getMessage(),
        ]);
    }
}
