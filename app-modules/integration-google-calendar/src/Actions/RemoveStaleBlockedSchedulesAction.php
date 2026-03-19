<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Actions;

use Illuminate\Support\Facades\DB;
use TresPontosTech\Consultants\Models\Consultant;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

readonly class RemoveStaleBlockedSchedulesAction
{
    public function handle(Consultant $consultant, array $syncedEventIds): void
    {
        Schedule::query()
            ->where('schedulable_type', $consultant->getMorphClass())
            ->where('schedulable_id', $consultant->getKey())
            ->where('schedule_type', ScheduleTypes::BLOCKED)
            ->whereJsonContains('metadata->source', 'google-calendar')
            ->whereNotNull(DB::raw("metadata->>'google_event_id'"))
            ->whereNotIn(DB::raw("metadata->>'google_event_id'"), $syncedEventIds)
            ->delete();
    }
}
