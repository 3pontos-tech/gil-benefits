<?php

declare(strict_types=1);

use TresPontosTech\IntegrationGoogleCalendar\Responses\CalendarEventsResponse;

it('parses events list and pagination tokens', function (): void {
    $response = CalendarEventsResponse::make([
        'items' => [
            [
                'id' => 'evt-1',
                'summary' => 'Test',
                'updated' => '2026-04-29T10:00:00.000Z',
                'start' => ['dateTime' => '2026-04-30T14:00:00-03:00'],
                'end' => ['dateTime' => '2026-04-30T15:00:00-03:00'],
            ],
        ],
        'nextPageToken' => 'page-token-abc',
        'nextSyncToken' => 'sync-token-xyz',
    ]);

    expect($response->events)->toHaveCount(1)
        ->and($response->nextPageToken)->toBe('page-token-abc')
        ->and($response->nextSyncToken)->toBe('sync-token-xyz');
});

it('returns null tokens when missing', function (): void {
    $response = CalendarEventsResponse::make([
        'items' => [],
    ]);

    expect($response->events)->toBeEmpty()
        ->and($response->nextPageToken)->toBeNull()
        ->and($response->nextSyncToken)->toBeNull();
});

it('handles missing items key', function (): void {
    $response = CalendarEventsResponse::make([]);

    expect($response->events)->toBeEmpty();
});
