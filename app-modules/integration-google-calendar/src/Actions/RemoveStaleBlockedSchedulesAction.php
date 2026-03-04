<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Actions;

use TresPontosTech\Consultants\Models\Consultant;
use Zap\Models\Schedule;

readonly class RemoveStaleBlockedSchedulesAction
{
    public function handle(Consultant $consultant, array $syncedEventIds): void
    {
        Schedule::query()
            ->where('schedulable_type', $consultant->getMorphClass())
            ->where('schedulable_id', $consultant->getKey())
            ->where('schedule_type', 'blocked')
            ->whereJsonContains('metadata->source', 'google-calendar')
            ->get()
            ->each(function (Schedule $schedule) use ($syncedEventIds): void {
                $eventId = $schedule->metadata['google_event_id'] ?? null;

                if ($eventId && ! in_array($eventId, $syncedEventIds)) {
                    $schedule->delete();
                }
            });
    }
}
