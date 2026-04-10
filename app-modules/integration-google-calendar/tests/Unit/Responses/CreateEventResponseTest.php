<?php

declare(strict_types=1);

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

it('selects the video entry point regardless of array order', function (): void {
    $response = CreateEventResponse::make([
        'id' => 'evt-ordered',
        'conferenceData' => [
            'entryPoints' => [
                ['uri' => 'tel:+55-11-99999-9999', 'entryPointType' => 'phone'],
                ['uri' => 'sip:meet@example.com', 'entryPointType' => 'sip'],
                ['uri' => 'https://meet.google.com/abc-defg-hij', 'entryPointType' => 'video'],
                ['uri' => 'https://more.example.com', 'entryPointType' => 'more'],
            ],
        ],
    ]);

    expect($response->meetLink)->toBe('https://meet.google.com/abc-defg-hij');
});

it('returns null meet link when no video entry point exists', function (): void {
    $response = CreateEventResponse::make([
        'id' => 'evt-no-video',
        'conferenceData' => [
            'entryPoints' => [
                ['uri' => 'tel:+55-11-99999-9999', 'entryPointType' => 'phone'],
            ],
        ],
    ]);

    expect($response->meetLink)->toBeNull();
});
