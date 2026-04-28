<?php

use Illuminate\Support\Facades\Http;
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

    $credPath = storage_path('testing/gc-test-credentials.json');
    @mkdir(dirname($credPath), 0755, true);
    file_put_contents($credPath, json_encode([
        'client_email' => 'sa@project.iam.gserviceaccount.com',
        'private_key' => $privateKey,
    ]));

    config(['google-calendar.service_account_credentials' => 'testing/gc-test-credentials.json']);

    Http::preventStrayRequests();

    $this->client = new GoogleCalendarClient;
});

afterEach(function (): void {
    @unlink(storage_path('testing/gc-test-credentials.json'));
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
