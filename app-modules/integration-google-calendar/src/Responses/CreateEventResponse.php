<?php

namespace TresPontosTech\IntegrationGoogleCalendar\Responses;

readonly class CreateEventResponse
{
    public function __construct(
        public string $eventId,
        public ?string $meetLink,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function make(array $payload): self
    {
        return new self(
            eventId: $payload['id'],
            meetLink: $payload['conferenceData']['entryPoints'][0]['uri'] ?? null,
        );
    }
}
