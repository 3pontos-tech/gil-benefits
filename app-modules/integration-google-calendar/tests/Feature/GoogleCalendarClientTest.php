<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;
use TresPontosTech\IntegrationGoogleCalendar\Responses\CalendarEventsResponse;
use TresPontosTech\IntegrationGoogleCalendar\Responses\CreateEventResponse;

beforeEach(function (): void {
    static $privateKey = null;

    if ($privateKey === null) {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);
    }

    @mkdir(storage_path('testing'), 0755, true);
    $credPath = tempnam(storage_path('testing'), 'gc-creds-');
    file_put_contents($credPath, json_encode([
        'client_email' => 'sa@project.iam.gserviceaccount.com',
        'private_key' => $privateKey,
    ]));

    $this->credPath = $credPath;

    config(['google-calendar.service_account_credentials' => 'testing/' . basename($credPath)]);

    Http::preventStrayRequests();

    $this->client = new GoogleCalendarClient;
});

afterEach(function (): void {
    @unlink($this->credPath);
});

// --- getAccessToken ---

it('throws a non-retryable exception for invalid_grant and unauthorized_client errors', function (string $errorCode): void {
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['error' => $errorCode], 200),
    ]);

    $exception = null;

    try {
        $this->client->getAccessToken('consultant@workspace.com');
    } catch (GoogleCalendarApiException $googleCalendarApiException) {
        $exception = $googleCalendarApiException;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->retryable)->toBeFalse();
})->with(['invalid_grant', 'unauthorized_client']);

it('throws a retryable exception on a generic token request failure', function (): void {
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['error' => 'server_error'], 500),
    ]);

    $exception = null;

    try {
        $this->client->getAccessToken('consultant@workspace.com');
    } catch (GoogleCalendarApiException $googleCalendarApiException) {
        $exception = $googleCalendarApiException;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->retryable)->toBeTrue();
});

it('returns the access token string on success', function (): void {
    Http::fake([
        'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'ya29.test-token'], 200),
    ]);

    $token = $this->client->getAccessToken('consultant@workspace.com');

    expect($token)->toBe('ya29.test-token');
});

// --- listEvents ---

it('returns a CalendarEventsResponse with events and nextPageToken', function (): void {
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response([
            'items' => [
                [
                    'id' => 'event-abc',
                    'summary' => 'Test Event',
                    'status' => 'confirmed',
                    'start' => ['dateTime' => '2026-05-01T09:00:00-03:00'],
                    'end' => ['dateTime' => '2026-05-01T10:00:00-03:00'],
                ],
            ],
            'nextPageToken' => 'next-page-xyz',
        ], 200),
    ]);

    $response = $this->client->listEvents('fake-token', 'primary', '2026-05-01T00:00:00Z', '2026-06-30T23:59:59Z');

    expect($response)->toBeInstanceOf(CalendarEventsResponse::class)
        ->and($response->events)->toHaveCount(1)
        ->and($response->events->first()->eventId)->toBe('event-abc')
        ->and($response->nextPageToken)->toBe('next-page-xyz');
});

it('passes the pageToken parameter to the API request', function (): void {
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response(['items' => [], 'nextPageToken' => null], 200),
    ]);

    $this->client->listEvents('fake-token', 'primary', '2026-05-01T00:00:00Z', '2026-06-30T23:59:59Z', 'my-page-token');

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'pageToken=my-page-token'));
});

it('throws a retryable exception on 429 quota exceeded', function (): void {
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response(['error' => 'rateLimitExceeded'], 429),
    ]);

    $exception = null;

    try {
        $this->client->listEvents('fake-token', 'primary', '2026-05-01T00:00:00Z', '2026-06-30T23:59:59Z');
    } catch (GoogleCalendarApiException $googleCalendarApiException) {
        $exception = $googleCalendarApiException;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->retryable)->toBeTrue();
});

// --- createEvent ---

it('returns a CreateEventResponse with eventId and meetLink on success', function (): void {
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response([
            'id' => 'created-event-id',
            'conferenceData' => [
                'entryPoints' => [
                    ['entryPointType' => 'video', 'uri' => 'https://meet.google.com/abc-defg-hij'],
                ],
            ],
        ], 200),
    ]);

    $response = $this->client->createEvent('fake-token', 'primary', []);

    expect($response)->toBeInstanceOf(CreateEventResponse::class)
        ->and($response->eventId)->toBe('created-event-id')
        ->and($response->meetLink)->toBe('https://meet.google.com/abc-defg-hij');
});

it('throws a retryable exception when createEvent fails', function (): void {
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response(['error' => 'forbidden'], 403),
    ]);

    $exception = null;

    try {
        $this->client->createEvent('fake-token', 'primary', []);
    } catch (GoogleCalendarApiException $googleCalendarApiException) {
        $exception = $googleCalendarApiException;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->retryable)->toBeTrue();
});

// --- deleteEvent ---

it('does not throw when deleteEvent succeeds with 204', function (): void {
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response('', 204),
    ]);

    expect(fn () => $this->client->deleteEvent('fake-token', 'primary', 'event-id'))->not->toThrow(GoogleCalendarApiException::class);
});

it('does not throw when deleteEvent receives 410 Gone (already deleted)', function (): void {
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response('', 410),
    ]);

    expect(fn () => $this->client->deleteEvent('fake-token', 'primary', 'gone-event-id'))->not->toThrow(GoogleCalendarApiException::class);
});

it('throws a retryable exception on deleteEvent server failure', function (): void {
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response(['error' => 'server_error'], 500),
    ]);

    $exception = null;

    try {
        $this->client->deleteEvent('fake-token', 'primary', 'event-id');
    } catch (GoogleCalendarApiException $googleCalendarApiException) {
        $exception = $googleCalendarApiException;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->retryable)->toBeTrue();
});

// --- notACalendarUser (403) ---

it('throws a non-retryable exception when the calendar user is not a Google Calendar user', function (string $method, array $arguments): void {
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response([
            'error' => [
                'errors' => [
                    ['domain' => 'calendar', 'reason' => 'notACalendarUser', 'message' => 'Not a calendar user'],
                ],
                'code' => 403,
                'message' => 'Not a calendar user',
            ],
        ], 403),
    ]);

    $exception = null;

    try {
        $this->client->{$method}(...$arguments);
    } catch (GoogleCalendarApiException $googleCalendarApiException) {
        $exception = $googleCalendarApiException;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->retryable)->toBeFalse()
        ->and($exception->getCode())->toBe(403);
})->with([
    'listEvents' => ['listEvents', ['fake-token', 'primary', '2026-05-01T00:00:00Z', '2026-06-30T23:59:59Z']],
    'createEvent' => ['createEvent', ['fake-token', 'primary', []]],
    'patchEvent' => ['patchEvent', ['fake-token', 'primary', 'event-id', []]],
    'deleteEvent' => ['deleteEvent', ['fake-token', 'primary', 'event-id']],
]);

it('keeps a 403 retryable when the reason is not notACalendarUser', function (): void {
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response([
            'error' => [
                'errors' => [['domain' => 'usageLimits', 'reason' => 'rateLimitExceeded']],
                'code' => 403,
            ],
        ], 403),
    ]);

    $exception = null;

    try {
        $this->client->deleteEvent('fake-token', 'primary', 'event-id');
    } catch (GoogleCalendarApiException $googleCalendarApiException) {
        $exception = $googleCalendarApiException;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->retryable)->toBeTrue();
});

it('keeps a non-403 response retryable even when the body mentions notACalendarUser', function (): void {
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response([
            'error' => ['errors' => [['reason' => 'notACalendarUser']], 'code' => 500],
        ], 500),
    ]);

    $exception = null;

    try {
        $this->client->deleteEvent('fake-token', 'primary', 'event-id');
    } catch (GoogleCalendarApiException $googleCalendarApiException) {
        $exception = $googleCalendarApiException;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->retryable)->toBeTrue();
});

// --- connection failures ---

it('retries a connection failure and succeeds on a later attempt', function (): void {
    Sleep::fake();

    Http::fakeSequence('https://www.googleapis.com/calendar/v3/calendars/*')
        ->pushFailedConnection('cURL error 28: SSL connection timeout')
        ->push(['items' => [], 'nextSyncToken' => 'sync-token-abc'], 200);

    $response = $this->client->listEvents('fake-token', 'primary', syncToken: 'previous-token');

    expect($response->nextSyncToken)->toBe('sync-token-abc');

    Http::assertSentCount(2);
    Sleep::assertSleptTimes(1);
});

it('throws a connection exception after exhausting the connection retries', function (): void {
    Sleep::fake();

    config(['google-calendar.connection_retry_times' => 3]);

    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::failedConnection('cURL error 28: SSL connection timeout'),
    ]);

    expect(fn () => $this->client->listEvents('fake-token', 'primary', '2026-05-01T00:00:00Z', '2026-06-30T23:59:59Z'))
        ->toThrow(ConnectionException::class);

    Http::assertSentCount(3);
});

it('does not retry HTTP error responses', function (): void {
    Sleep::fake();

    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response(['error' => 'server_error'], 500),
    ]);

    expect(fn () => $this->client->listEvents('fake-token', 'primary', '2026-05-01T00:00:00Z', '2026-06-30T23:59:59Z'))
        ->toThrow(GoogleCalendarApiException::class);

    Http::assertSentCount(1);
    Sleep::assertNeverSlept();
});

it('retries connection failures on the idempotent write operations', function (string $method, array $arguments): void {
    Sleep::fake();

    Http::fakeSequence('https://www.googleapis.com/calendar/v3/calendars/*')
        ->pushFailedConnection('cURL error 28: SSL connection timeout')
        ->push('', 204);

    $this->client->{$method}(...$arguments);

    Http::assertSentCount(2);
})->with([
    'patchEvent' => ['patchEvent', ['fake-token', 'primary', 'event-id', []]],
    'deleteEvent' => ['deleteEvent', ['fake-token', 'primary', 'event-id']],
]);

it('retries connection failures when fetching the access token', function (): void {
    Sleep::fake();

    Http::fakeSequence('https://oauth2.googleapis.com/token')
        ->pushFailedConnection('cURL error 28: SSL connection timeout')
        ->push(['access_token' => 'ya29.retried-token'], 200);

    expect($this->client->getAccessToken('consultant@workspace.com'))->toBe('ya29.retried-token');

    Http::assertSentCount(2);
});

it('never retries createEvent, so a timed out create cannot duplicate the event', function (): void {
    Sleep::fake();

    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::failedConnection('cURL error 28: SSL connection timeout'),
    ]);

    expect(fn () => $this->client->createEvent('fake-token', 'primary', []))
        ->toThrow(ConnectionException::class);

    Http::assertSentCount(1);
    Sleep::assertNeverSlept();
});

it('keeps a 403 retryable when error.errors is not the expected array shape', function (): void {
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response(['error' => ['errors' => 'unexpected']], 403),
    ]);

    $exception = null;

    try {
        $this->client->deleteEvent('fake-token', 'primary', 'event-id');
    } catch (GoogleCalendarApiException $googleCalendarApiException) {
        $exception = $googleCalendarApiException;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->retryable)->toBeTrue();
});

it('keeps a 403 with a non-JSON body retryable', function (): void {
    Http::fake([
        'https://www.googleapis.com/calendar/v3/calendars/*' => Http::response('Forbidden', 403),
    ]);

    $exception = null;

    try {
        $this->client->deleteEvent('fake-token', 'primary', 'event-id');
    } catch (GoogleCalendarApiException $googleCalendarApiException) {
        $exception = $googleCalendarApiException;
    }

    expect($exception)->not->toBeNull()
        ->and($exception->retryable)->toBeTrue();
});
