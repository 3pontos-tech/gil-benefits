<?php

namespace TresPontosTech\IntegrationGoogleCalendar\DTO;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;

readonly class GoogleEventDTO
{
    public function __construct(
        public string $eventId,
        public string $summary,
        public Carbon $start,
        public Carbon $end,
        public bool $isAllDay,
        public bool $isCancelled,
    ) {}

    public static function fromApiPayload(array $event): self
    {
        $isCancelled = ($event['status'] ?? '') === 'cancelled';
        $isAllDay = isset($event['start']['date']) && ! isset($event['start']['dateTime']);

        $appTimezone = config('app.timezone');

        if ($isAllDay) {
            $start = Date::parse($event['start']['date'])->startOfDay();
            $end = Date::parse($event['end']['date'])->startOfDay();
        } else {
            $start = Date::parse($event['start']['dateTime'] ?? now())->setTimezone($appTimezone);
            $end = Date::parse($event['end']['dateTime'] ?? now())->setTimezone($appTimezone);
        }

        return new self(
            eventId: $event['id'],
            summary: $event['summary'] ?? '(sem título)',
            start: $start,
            end: $end,
            isAllDay: $isAllDay,
            isCancelled: $isCancelled,
        );
    }
}
