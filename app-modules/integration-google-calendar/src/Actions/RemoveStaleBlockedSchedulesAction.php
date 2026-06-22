<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationGoogleCalendar\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Consultants\Models\Consultant;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

readonly class RemoveStaleBlockedSchedulesAction
{
    public function handle(Consultant $consultant, array $syncedEventIds): void
    {
        DB::transaction(function () use ($consultant, $syncedEventIds): void {
            /** @var Collection<int, Schedule> $schedules */
            $schedules = Schedule::query()
                ->where('schedulable_type', $consultant->getMorphClass())
                ->where('schedulable_id', $consultant->getKey())
                ->where('schedule_type', ScheduleTypes::BLOCKED)
                ->whereJsonContains('metadata->source', 'google-calendar')
                ->get(['id', 'metadata']);

            $staleIds = $schedules
                ->filter(function (Schedule $schedule) use ($syncedEventIds): bool {
                    $eventId = $schedule->metadata['google_event_id'] ?? null;

                    return filled($eventId) && ! in_array($eventId, $syncedEventIds, true);
                })
                ->pluck('id');

            if ($staleIds->isEmpty()) {
                return;
            }

            Schedule::query()->whereIn('id', $staleIds)->delete();
        });
    }
}
