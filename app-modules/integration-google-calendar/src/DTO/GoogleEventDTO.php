<?php

namespace TresPontosTech\IntegrationGoogleCalendar\DTO;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;

readonly class GoogleEventDTO
{
    public function __construct(
        public string $eventId,
        public string $summary,
        public Carbon $start,
        public Carbon $end,
        public bool $isAllDay,
        public bool $isCancelled,
        public ?Carbon $updated,
    ) {}

    public static function fromApiPayload(array $event): self
    {
        $isCancelled = ($event['status'] ?? '') === 'cancelled';
        $isAllDay = isset($event['start']['date']) && ! isset($event['start']['dateTime']);
        $appTimezone = config('app.timezone');
        $updated = isset($event['updated']) ? Date::parse($event['updated']) : null;

        if ($isCancelled) {
            return new self(
                eventId: $event['id'],
                summary: $event['summary'] ?? '(sem título)',
                start: Date::now(),
                end: Date::now(),
                isAllDay: false,
                isCancelled: true,
                updated: $updated,
            );
        }

        if ($isAllDay) {
            return new self(
                eventId: $event['id'],
                summary: $event['summary'] ?? '(sem título)',
                start: Date::parse($event['start']['date']),
                end: Date::parse($event['end']['date']),
                isAllDay: true,
                isCancelled: false,
                updated: $updated,
            );
        }

        throw_unless(isset($event['start']['dateTime'], $event['end']['dateTime']), InvalidArgumentException::class, 'Non-all-day event missing dateTime fields');

        return new self(
            eventId: $event['id'],
            summary: $event['summary'] ?? '(sem título)',
            start: Date::parse($event['start']['dateTime'])->setTimezone($appTimezone),
            end: Date::parse($event['end']['dateTime'])->setTimezone($appTimezone),
            isAllDay: false,
            isCancelled: false,
            updated: $updated,
        );
    }
}
