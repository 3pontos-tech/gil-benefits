<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationGoogleCalendar\Support;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use TresPontosTech\Consultants\Models\Consultant;
use Zap\Enums\ScheduleTypes;
use Zap\Models\Schedule;

/**
 * Preloads a consultant's schedules once per calendar sync so that
 * UpsertBlockedScheduleAction can resolve overlaps and existing blocks
 * in memory, instead of running two queries per Google Calendar event.
 */
readonly class ConsultantSyncContext
{
    /**
     * @param  array<string, array<array-key, mixed>>  $blockedMetadataByEventId
     * @param  Collection<int, Schedule>  $appointments
     */
    private function __construct(
        private array $blockedMetadataByEventId,
        private Collection $appointments,
    ) {}

    public static function for(Consultant $consultant): self
    {
        // Mirrors UpsertBlockedScheduleAction's per-event lookup: any BLOCKED
        // schedule carrying a google_event_id, regardless of source. Blocks
        // without one are dropped below via the empty-key reject.
        /** @var Collection<int, Schedule> $blockedSchedules */
        $blockedSchedules = Schedule::query()
            ->where('schedulable_type', $consultant->getMorphClass())
            ->where('schedulable_id', $consultant->getKey())
            ->where('schedule_type', ScheduleTypes::BLOCKED)
            ->get(['metadata']);

        $blockedMetadataByEventId = [];

        foreach ($blockedSchedules as $schedule) {
            $metadata = $schedule->metadata;

            if (! is_array($metadata)) {
                continue;
            }

            $eventId = $metadata['google_event_id'] ?? null;

            if (is_string($eventId) && $eventId !== '') {
                $blockedMetadataByEventId[$eventId] = $metadata;
            }
        }

        /** @var Collection<int, Schedule> $appointments */
        $appointments = Schedule::query()
            ->where('schedulable_type', $consultant->getMorphClass())
            ->where('schedulable_id', $consultant->getKey())
            ->where('schedule_type', ScheduleTypes::APPOINTMENT)
            ->with('periods:id,schedule_id,start_time,end_time')
            ->get(['id', 'start_date', 'end_date']);

        return new self($blockedMetadataByEventId, $appointments);
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function metadataForEvent(string $eventId): ?array
    {
        return $this->blockedMetadataByEventId[$eventId] ?? null;
    }

    /**
     * Mirrors the SQL overlap check: an appointment whose date range overlaps
     * the event and that has a period overlapping the given time window.
     * Schedules with a null end_date are excluded, matching `end_date > ?`.
     */
    public function hasOverlappingAppointment(
        string $startDate,
        string $checkEndDate,
        string $checkStartTime,
        string $checkEndTime,
    ): bool {
        $windowStart = $this->minutesOfDay($checkStartTime);
        $windowEnd = $this->minutesOfDay($checkEndTime);

        return $this->appointments->contains(function (Schedule $appointment) use ($startDate, $checkEndDate, $windowStart, $windowEnd): bool {
            if ($appointment->end_date === null) {
                return false;
            }

            $overlapsDateRange = $appointment->start_date->toDateString() < $checkEndDate
                && $appointment->end_date->toDateString() > $startDate;

            if (! $overlapsDateRange) {
                return false;
            }

            return $appointment->periods->contains(function (object $period) use ($windowStart, $windowEnd): bool {
                if ($period->start_time === null || $period->end_time === null) {
                    return false;
                }

                return $this->minutesOfDay($period->start_time) < $windowEnd
                    && $this->minutesOfDay($period->end_time) > $windowStart;
            });
        });
    }

    private function minutesOfDay(Carbon|string $time): int
    {
        $value = $time instanceof Carbon ? $time->format('H:i') : $time;

        [$hours, $minutes] = array_pad(explode(':', $value), 2, '0');

        return ((int) $hours) * 60 + (int) $minutes;
    }
}
