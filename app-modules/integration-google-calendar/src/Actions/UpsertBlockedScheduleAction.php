<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Actions;

use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\DTO\GoogleEventDTO;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

readonly class UpsertBlockedScheduleAction
{
    public function handle(Consultant $consultant, GoogleEventDTO $event): void
    {
        $startDate = $event->start->toDateString();
        $startTime = $event->start->format('H:i');

        $endIsNextMidnight = $event->end->format('H:i') === '00:00'
            && $event->end->isSameDay($event->start->copy()->addDay());

        $isSameDay = $event->start->isSameDay($event->end) || $endIsNextMidnight;

        // Normalize effective time range for overlap detection
        if ($event->isAllDay || ! $isSameDay) {
            $checkStartTime = '00:00';
            $checkEndTime = '23:59';
            $checkEndDate = $event->isAllDay
                ? $event->end->toDateString()
                : $event->end->copy()->addDay()->toDateString();
        } else {
            $checkStartTime = $startTime;
            $checkEndTime = $endIsNextMidnight ? '23:59' : $event->end->format('H:i');
            $checkEndDate = $event->start->copy()->addDay()->toDateString();
        }

        $existingAppointment = Schedule::query()
            ->where('schedulable_type', $consultant->getMorphClass())
            ->where('schedulable_id', $consultant->getKey())
            ->where('schedule_type', 'appointment')
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
            ->where('schedule_type', 'blocked')
            ->whereJsonContains('metadata->google_event_id', $event->eventId)
            ->delete();

        if ($event->isAllDay) {
            // All-day: end_date from Google is already exclusive (next day)
            Zap::for($consultant)
                ->named($event->summary)
                ->blocked()
                ->allowOverlap()
                ->from($startDate)
                ->to($event->end->toDateString())
                ->addPeriod('00:00', '23:59')
                ->withMetadata(['google_event_id' => $event->eventId, 'source' => 'google-calendar'])
                ->save();

            return;
        }

        if ($isSameDay) {
            // If event ends exactly at midnight, cap at 23:59 (Zap requires end_time > start_time)
            $endTime = $endIsNextMidnight ? '23:59' : $event->end->format('H:i');

            Zap::for($consultant)
                ->named($event->summary)
                ->blocked()
                ->allowOverlap()
                ->from($startDate)
                ->to($event->start->copy()->addDay()->toDateString())
                ->addPeriod($startTime, $endTime)
                ->withMetadata(['google_event_id' => $event->eventId, 'source' => 'google-calendar'])
                ->save();

            return;
        }

        // Multi-day timed event: block all days entirely
        Zap::for($consultant)
            ->named($event->summary)
            ->blocked()
            ->allowOverlap()
            ->from($startDate)
            ->to($event->end->copy()->addDay()->toDateString())
            ->addPeriod('00:00', '23:59')
            ->withMetadata(['google_event_id' => $event->eventId, 'source' => 'google-calendar'])
            ->save();
    }
}
