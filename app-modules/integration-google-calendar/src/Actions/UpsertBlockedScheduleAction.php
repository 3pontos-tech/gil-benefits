<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Actions;

use Illuminate\Support\Facades\DB;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\DTO\GoogleEventDTO;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

readonly class UpsertBlockedScheduleAction
{
    public function handle(Consultant $consultant, GoogleEventDTO $event): void
    {
        $startDate = $event->start->toDateString();
        $startTime = $event->start->format('H:i');

        [$checkStartTime, $checkEndTime, $checkEndDate] = $this->resolveCheckRange($event, $startTime);

        $hasOverlappingAppointment = Schedule::query()
            ->where('schedulable_type', $consultant->getMorphClass())
            ->where('schedulable_id', $consultant->getKey())
            ->where('schedule_type', ScheduleTypes::APPOINTMENT)
            ->where('start_date', '<', $checkEndDate)
            ->where('end_date', '>', $startDate)
            ->whereHas('periods', fn ($q) => $q
                ->where('start_time', '<', $checkEndTime)
                ->where('end_time', '>', $checkStartTime)
            )
            ->exists();

        $existingMetadata = Schedule::query()
            ->where('schedulable_type', $consultant->getMorphClass())
            ->where('schedulable_id', $consultant->getKey())
            ->where('schedule_type', ScheduleTypes::BLOCKED)
            ->whereJsonContains('metadata->google_event_id', $event->eventId)
            ->value('metadata');

        if ($hasOverlappingAppointment) {
            if ($existingMetadata !== null) {
                $this->deleteByEventId($consultant, $event->eventId);
            }

            return;
        }

        $eventUpdated = $event->updated?->toIso8601String();

        if ($existingMetadata !== null
            && $eventUpdated !== null
            && ($existingMetadata['updated'] ?? null) === $eventUpdated) {
            return;
        }

        DB::transaction(function () use ($consultant, $event, $existingMetadata, $eventUpdated, $startDate, $startTime): void {
            if ($existingMetadata !== null) {
                $this->deleteByEventId($consultant, $event->eventId);
            }

            $this->createBlockedSchedule($consultant, $event, $startDate, $startTime, $eventUpdated);
        });
    }

    private function createBlockedSchedule(
        Consultant $consultant,
        GoogleEventDTO $event,
        string $startDate,
        string $startTime,
        ?string $eventUpdated,
    ): void {
        $metadata = [
            'google_event_id' => $event->eventId,
            'source' => 'google-calendar',
            'updated' => $eventUpdated,
        ];

        [$endDate, $period] = $this->resolveCreateRange($event, $startTime);

        Zap::for($consultant)
            ->named($event->summary)
            ->blocked()
            ->allowOverlap()
            ->from($startDate)
            ->to($endDate)
            ->addPeriod($period[0], $period[1])
            ->withMetadata($metadata)
            ->save();
    }

    /**
     * @return array{0: string|null, 1: array{0: string, 1: string}}
     */
    private function resolveCreateRange(GoogleEventDTO $event, string $startTime): array
    {
        if ($event->isAllDay) {
            $effectiveEnd = $event->end->copy()->subDay();
            $endDate = $effectiveEnd->isSameDay($event->start)
                ? null
                : $effectiveEnd->toDateString();

            return [$endDate, ['00:00', '23:59']];
        }

        if ($this->isSameDay($event)) {
            $endTime = $this->endsAtNextMidnight($event) ? '23:59' : $event->end->format('H:i');

            return [null, [$startTime, $endTime]];
        }

        return [$event->end->toDateString(), ['00:00', '23:59']];
    }

    private function deleteByEventId(Consultant $consultant, string $eventId): void
    {
        Schedule::query()
            ->where('schedulable_type', $consultant->getMorphClass())
            ->where('schedulable_id', $consultant->getKey())
            ->where('schedule_type', ScheduleTypes::BLOCKED)
            ->whereJsonContains('metadata->google_event_id', $eventId)
            ->delete();
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function resolveCheckRange(GoogleEventDTO $event, string $startTime): array
    {
        return match (true) {
            $event->isAllDay, ! $this->isSameDay($event) => [
                '00:00',
                '23:59',
                $event->isAllDay
                    ? $event->end->toDateString()
                    : $event->end->copy()->addDay()->toDateString(),
            ],
            default => [
                $startTime,
                $this->endsAtNextMidnight($event) ? '23:59' : $event->end->format('H:i'),
                $event->start->copy()->addDay()->toDateString(),
            ],
        };
    }

    private function endsAtNextMidnight(GoogleEventDTO $event): bool
    {
        return $event->end->format('H:i') === '00:00'
            && $event->end->isSameDay($event->start->copy()->addDay());
    }

    private function isSameDay(GoogleEventDTO $event): bool
    {
        if ($event->start->isSameDay($event->end)) {
            return true;
        }

        return $this->endsAtNextMidnight($event);
    }
}
