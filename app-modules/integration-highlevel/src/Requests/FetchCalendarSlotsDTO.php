<?php

namespace TresPontosTech\IntegrationHighlevel\Requests;

use Carbon\CarbonInterface;
use JsonSerializable;

class FetchCalendarSlotsDTO implements JsonSerializable
{
    public function __construct(
        public string $calendarId,
        public CarbonInterface $startDate,
        public CarbonInterface $endDate,
        public ?string $timezone = 'America/Sao_Paulo',
    ) {}

    public static function make(
        CarbonInterface $startDate,
        CarbonInterface $endDate,
    ): self {
        return new self(
            calendarId: config('services.highlevel.calendar', 'lAwKkZ3QFKKGSrFPTXNf'),
            startDate: $startDate,
            endDate: $endDate,
            timezone: config('app.timezone', 'America/Sao_Paulo'),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'startDate' => $this->startDate->getTimestampMs(),
            'endDate' => $this->endDate->getTimestampMs(),
            'timezone' => $this->timezone,
        ];
    }
}
