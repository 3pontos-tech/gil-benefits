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

        $existingAppointment = Schedule::query()
            ->where('schedulable_type', $consultant->getMorphClass())
            ->where('schedulable_id', $consultant->getKey())
            ->where('schedule_type', 'appointment')
            ->whereHas('periods', fn ($q) => $q->where('start_time', $startTime))
            ->where('start_date', $startDate)
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

        $isSameDay = $event->start->toDateString() === $event->end->toDateString();

        if ($isSameDay) {
            Zap::for($consultant)
                ->named($event->summary)
                ->blocked()
                ->allowOverlap()
                ->from($startDate)
                ->to($event->start->copy()->addDay()->toDateString())
                ->addPeriod($startTime, $event->end->format('H:i'))
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
