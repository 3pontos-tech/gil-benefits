<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Responses;

use Illuminate\Support\Collection;
use TresPontosTech\IntegrationGoogleCalendar\DTO\GoogleEventDTO;

readonly class CalendarEventsResponse
{
    /**
     * @param  Collection<int, GoogleEventDTO>  $events
     */
    public function __construct(
        public Collection $events,
        public ?string $nextPageToken,
        public ?string $nextSyncToken,
    ) {}

    /**
     * @param  array{items?: array<int, array<string, mixed>>, nextPageToken?: string|null, nextSyncToken?: string|null}  $payload
     */
    public static function make(array $payload): self
    {
        $events = collect($payload['items'] ?? [])
            ->map(fn (array $item): GoogleEventDTO => GoogleEventDTO::fromApiPayload($item));

        return new self(
            events: $events,
            nextPageToken: $payload['nextPageToken'] ?? null,
            nextSyncToken: $payload['nextSyncToken'] ?? null,
        );
    }
}
