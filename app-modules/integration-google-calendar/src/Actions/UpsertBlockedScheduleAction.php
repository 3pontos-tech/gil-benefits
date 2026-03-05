<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Actions;

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

        $existingAppointment = Schedule::query()
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

        if ($existingAppointment) {
            return;
        }

        Schedule::query()
            ->where('schedulable_type', $consultant->getMorphClass())
            ->where('schedulable_id', $consultant->getKey())
            ->where('schedule_type', ScheduleTypes::BLOCKED)
            ->whereJsonContains('metadata->google_event_id', $event->eventId)
            ->delete();

        if ($event->isAllDay) {
            // Google all-day end.date is exclusive (e.g. Jun/15 event → end: Jun/16).
            // Zap's forDate uses end_date as inclusive, so we convert to inclusive by subtracting one day.
            // For a 1-day all-day event the effective end equals start, so we use null (forDate matches start_date only).
            $effectiveEnd = $event->end->copy()->subDay();
            $endDate = $effectiveEnd->isSameDay($event->start)
                ? null
                : $effectiveEnd->toDateString();

            Zap::for($consultant)
                ->named($event->summary)
                ->blocked()
                ->allowOverlap()
                ->from($startDate)
                ->to($endDate)
                ->addPeriod('00:00', '23:59')
                ->withMetadata(['google_event_id' => $event->eventId, 'source' => 'google-calendar'])
                ->save();

            return;
        }

        if ($this->isSameDay($event)) {
            // If event ends exactly at midnight, cap at 23:59 (Zap requires end_time > start_time)
            $endTime = $this->endsAtNextMidnight($event) ? '23:59' : $event->end->format('H:i');

            // Use null end_date so forDate matches only the exact start_date (avoids off-by-one from inclusive end_date)
            Zap::for($consultant)
                ->named($event->summary)
                ->blocked()
                ->allowOverlap()
                ->from($startDate)
                ->to(null)
                ->addPeriod($startTime, $endTime)
                ->withMetadata(['google_event_id' => $event->eventId, 'source' => 'google-calendar'])
                ->save();

            return;
        }

        // Multi-day timed event: end.dateTime is the actual end moment (not exclusive like all-day).
        // Zap's forDate treats end_date inclusively, so we use the end date as-is without addDay().
        Zap::for($consultant)
            ->named($event->summary)
            ->blocked()
            ->allowOverlap()
            ->from($startDate)
            ->to($event->end->toDateString())
            ->addPeriod('00:00', '23:59')
            ->withMetadata(['google_event_id' => $event->eventId, 'source' => 'google-calendar'])
            ->save();
    }

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
        return $event->start->isSameDay($event->end) || $this->endsAtNextMidnight($event);
    }
}
