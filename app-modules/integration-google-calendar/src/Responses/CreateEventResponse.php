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
            meetLink: self::extractMeetLink($payload['conferenceData']['entryPoints'] ?? []),
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $entryPoints
     */
    private static function extractMeetLink(array $entryPoints): ?string
    {
        foreach ($entryPoints as $entryPoint) {
            if (($entryPoint['entryPointType'] ?? null) === 'video') {
                return $entryPoint['uri'] ?? null;
            }
        }

        return null;
    }
}
