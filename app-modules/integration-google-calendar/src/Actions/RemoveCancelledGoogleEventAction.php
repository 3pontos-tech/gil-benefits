<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Actions;

use TresPontosTech\Consultants\Models\Consultant;
use Zap\Models\Schedule;

readonly class RemoveCancelledGoogleEventAction
{
    public function handle(Consultant $consultant, string $eventId): void
    {
        Schedule::query()
            ->where('schedulable_type', $consultant->getMorphClass())
            ->where('schedulable_id', $consultant->getKey())
            ->where('schedule_type', 'blocked')
            ->whereJsonContains('metadata->google_event_id', $eventId)
            ->delete();
    }
}
