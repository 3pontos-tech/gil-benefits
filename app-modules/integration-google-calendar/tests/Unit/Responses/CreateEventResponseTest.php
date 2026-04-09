<?php

use TresPontosTech\IntegrationGoogleCalendar\Responses\CreateEventResponse;

it('parses eventId and meet link from full payload', function (): void {
    $response = CreateEventResponse::make([
        'id' => 'evt-123',
        'conferenceData' => [
            'entryPoints' => [
                ['uri' => 'https://meet.google.com/xyz-abcd-efg', 'entryPointType' => 'video'],
            ],
        ],
    ]);

    expect($response->eventId)->toBe('evt-123')
        ->and($response->meetLink)->toBe('https://meet.google.com/xyz-abcd-efg');
});

it('returns null meet link when conferenceData is missing', function (): void {
    $response = CreateEventResponse::make([
        'id' => 'evt-456',
    ]);

    expect($response->eventId)->toBe('evt-456')
        ->and($response->meetLink)->toBeNull();
});

it('returns null meet link when entryPoints is empty', function (): void {
    $response = CreateEventResponse::make([
        'id' => 'evt-789',
        'conferenceData' => [
            'entryPoints' => [],
        ],
    ]);

    expect($response->eventId)->toBe('evt-789')
        ->and($response->meetLink)->toBeNull();
});
