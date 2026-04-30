<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\RemoveCancelledGoogleEventAction;
use TresPontosTech\IntegrationGoogleCalendar\Actions\RemoveStaleBlockedSchedulesAction;
use TresPontosTech\IntegrationGoogleCalendar\Actions\SyncConsultantCalendarAction;
use TresPontosTech\IntegrationGoogleCalendar\Actions\UpsertBlockedScheduleAction;
use TresPontosTech\IntegrationGoogleCalendar\DTO\GoogleEventDTO;
use TresPontosTech\IntegrationGoogleCalendar\Exceptions\GoogleCalendarApiException;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;
use TresPontosTech\IntegrationGoogleCalendar\Responses\CalendarEventsResponse;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

beforeEach(function (): void {
    $this->consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
});

afterEach(function (): void {
    Date::setTestNow();
});

function buildAction(GoogleCalendarClient $client): SyncConsultantCalendarAction
{
    return new SyncConsultantCalendarAction(
        $client,
        resolve(UpsertBlockedScheduleAction::class),
        resolve(RemoveCancelledGoogleEventAction::class),
        resolve(RemoveStaleBlockedSchedulesAction::class),
    );
}

function emptyResponse(?string $nextSyncToken = null): CalendarEventsResponse
{
    return new CalendarEventsResponse(
        events: new Collection,
        nextPageToken: null,
        nextSyncToken: $nextSyncToken,
    );
}

function eventDto(string $id, string $updated = '2026-04-29T10:00:00Z'): GoogleEventDTO
{
    return new GoogleEventDTO(
        eventId: $id,
        summary: 'Reunião',
        start: Date::parse('2026-05-15 14:00:00'),
        end: Date::parse('2026-05-15 15:00:00'),
        isAllDay: false,
        isCancelled: false,
        updated: Date::parse($updated),
    );
}

it('does a full sync when consultant has no sync token and persists the new token', function (): void {
    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->andReturn('access');
    $client->shouldReceive('listEvents')
        ->once()
        ->withArgs(function (...$args): bool {
            $syncToken = $args[5] ?? null;

            return $syncToken === null;
        })
        ->andReturn(new CalendarEventsResponse(
            events: collect([eventDto('evt-1')]),
            nextPageToken: null,
            nextSyncToken: 'fresh-token',
        ));

    buildAction($client)->handle($this->consultant);

    $this->consultant->refresh();
    expect($this->consultant->google_calendar_sync_token)->toBe('fresh-token')
        ->and($this->consultant->google_calendar_synced_at)->not->toBeNull()
        ->and(Schedule::query()
            ->where('schedule_type', ScheduleTypes::BLOCKED)
            ->whereJsonContains('metadata->google_event_id', 'evt-1')
            ->exists())->toBeTrue();
});

it('does an incremental sync when consultant has a recent sync token', function (): void {
    $this->consultant->update([
        'google_calendar_sync_token' => 'existing-token',
        'google_calendar_synced_at' => Date::now()->subMinutes(10),
    ]);

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->andReturn('access');
    $client->shouldReceive('listEvents')
        ->once()
        ->withArgs(function ($accessToken, $calendarId, $timeMin = null, $timeMax = null, $pageToken = null, $syncToken = null): bool {
            return $syncToken === 'existing-token' && $timeMin === null && $timeMax === null;
        })
        ->andReturn(emptyResponse('next-token'));

    buildAction($client)->handle($this->consultant);

    expect($this->consultant->refresh()->google_calendar_sync_token)->toBe('next-token');
});

it('forces full sync when last sync is older than 24h', function (): void {
    $this->consultant->update([
        'google_calendar_sync_token' => 'stale-token',
        'google_calendar_synced_at' => Date::now()->subHours(25),
    ]);

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->andReturn('access');
    $client->shouldReceive('listEvents')
        ->once()
        ->withArgs(function ($accessToken, $calendarId, $timeMin = null, $timeMax = null, $pageToken = null, $syncToken = null): bool {
            return $syncToken === null && filled($timeMin) && filled($timeMax);
        })
        ->andReturn(emptyResponse('refreshed-token'));

    buildAction($client)->handle($this->consultant);

    expect($this->consultant->refresh()->google_calendar_sync_token)->toBe('refreshed-token');
});

it('falls back to full sync when token expires (410)', function (): void {
    $this->consultant->update([
        'google_calendar_sync_token' => 'expired-token',
        'google_calendar_synced_at' => Date::now()->subMinutes(5),
    ]);

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->andReturn('access');

    $client->shouldReceive('listEvents')
        ->once()
        ->withArgs(fn ($accessToken, $calendarId, $timeMin = null, $timeMax = null, $pageToken = null, $syncToken = null): bool => $syncToken === 'expired-token')
        ->andThrow(new GoogleCalendarApiException('gone', 410));

    $client->shouldReceive('listEvents')
        ->once()
        ->withArgs(fn ($accessToken, $calendarId, $timeMin = null, $timeMax = null, $pageToken = null, $syncToken = null): bool => $syncToken === null && filled($timeMin))
        ->andReturn(emptyResponse('post-recovery-token'));

    buildAction($client)->handle($this->consultant);

    expect($this->consultant->refresh()->google_calendar_sync_token)->toBe('post-recovery-token');
});

it('removes stale blocked schedules during full sync only', function (): void {
    Zap::for($this->consultant)
        ->named('Stale block')
        ->blocked()
        ->allowOverlap()
        ->from('2026-05-20')
        ->to(null)
        ->addPeriod('09:00', '10:00')
        ->withMetadata([
            'google_event_id' => 'gone-event',
            'source' => 'google-calendar',
            'updated' => '2026-04-01T00:00:00+00:00',
        ])
        ->save();

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->andReturn('access');
    $client->shouldReceive('listEvents')->andReturn(emptyResponse('new-token'));

    buildAction($client)->handle($this->consultant);

    expect(Schedule::query()
        ->whereJsonContains('metadata->google_event_id', 'gone-event')
        ->exists())->toBeFalse();
});

it('does not run stale cleanup during incremental sync', function (): void {
    $this->consultant->update([
        'google_calendar_sync_token' => 'token',
        'google_calendar_synced_at' => Date::now()->subMinutes(5),
    ]);

    Zap::for($this->consultant)
        ->named('Untouched block')
        ->blocked()
        ->allowOverlap()
        ->from('2026-05-20')
        ->to(null)
        ->addPeriod('09:00', '10:00')
        ->withMetadata([
            'google_event_id' => 'untouched',
            'source' => 'google-calendar',
            'updated' => '2026-04-01T00:00:00+00:00',
        ])
        ->save();

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->andReturn('access');
    $client->shouldReceive('listEvents')->andReturn(emptyResponse('next-token'));

    buildAction($client)->handle($this->consultant);

    expect(Schedule::query()
        ->whereJsonContains('metadata->google_event_id', 'untouched')
        ->exists())->toBeTrue();
});

it('creates a blocked schedule for each active event returned', function (): void {
    $event = new GoogleEventDTO('event-1', 'Meeting', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false, null);

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->with('consultant@workspace.com')->andReturn('fake-token');
    $client->shouldReceive('listEvents')->once()->andReturn(
        new CalendarEventsResponse(collect([$event]), null, null)
    );

    buildAction($client)->handle($this->consultant);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'event-1')
        ->exists()
    )->toBeTrue();
});

it('removes a blocked schedule when the event is returned as cancelled', function (): void {
    Zap::for($this->consultant)
        ->named('Old Block')
        ->blocked()
        ->allowOverlap()
        ->from('2026-05-01')
        ->to(null)
        ->addPeriod('09:00', '10:00')
        ->withMetadata(['google_event_id' => 'event-cancelled', 'source' => 'google-calendar'])
        ->save();

    $cancelled = new GoogleEventDTO('event-cancelled', '', Date::now(), Date::now(), false, true, null);

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->with('consultant@workspace.com')->andReturn('fake-token');
    $client->shouldReceive('listEvents')->once()->andReturn(
        new CalendarEventsResponse(collect([$cancelled]), null, null)
    );

    buildAction($client)->handle($this->consultant);

    expect(Schedule::query()
        ->whereJsonContains('metadata->google_event_id', 'event-cancelled')
        ->exists()
    )->toBeFalse();
});

it('removes stale blocked schedules that are no longer in the API response', function (): void {
    Zap::for($this->consultant)
        ->named('Stale Block')
        ->blocked()
        ->allowOverlap()
        ->from(Date::now()->addDays(5)->toDateString())
        ->to(null)
        ->addPeriod('10:00', '11:00')
        ->withMetadata(['google_event_id' => 'stale-event', 'source' => 'google-calendar'])
        ->save();

    $freshEvent = new GoogleEventDTO('fresh-event', 'Fresh Meeting', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false, null);

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->with('consultant@workspace.com')->andReturn('fake-token');
    $client->shouldReceive('listEvents')->once()->andReturn(
        new CalendarEventsResponse(collect([$freshEvent]), null, null)
    );

    buildAction($client)->handle($this->consultant);

    expect(Schedule::query()->whereJsonContains('metadata->google_event_id', 'stale-event')->exists())->toBeFalse()
        ->and(Schedule::query()->whereJsonContains('metadata->google_event_id', 'fresh-event')->exists())->toBeTrue();
});

it('paginates through all pages collecting events from each', function (): void {
    $eventPage1 = new GoogleEventDTO('event-page-1', 'E1', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false, null);
    $eventPage2 = new GoogleEventDTO('event-page-2', 'E2', Date::parse('2026-05-02 09:00'), Date::parse('2026-05-02 10:00'), false, false, null);

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->with('consultant@workspace.com')->andReturn('fake-token');

    $client->shouldReceive('listEvents')
        ->once()
        ->withArgs(fn ($accessToken, $calendarId, $timeMin = null, $timeMax = null, $pageToken = null, $syncToken = null): bool => $pageToken === null)
        ->andReturn(new CalendarEventsResponse(collect([$eventPage1]), 'page-token-2', null));

    $client->shouldReceive('listEvents')
        ->once()
        ->withArgs(fn ($accessToken, $calendarId, $timeMin = null, $timeMax = null, $pageToken = null, $syncToken = null): bool => $pageToken === 'page-token-2')
        ->andReturn(new CalendarEventsResponse(collect([$eventPage2]), null, null));

    buildAction($client)->handle($this->consultant);

    expect(Schedule::query()->whereJsonContains('metadata->google_event_id', 'event-page-1')->exists())->toBeTrue()
        ->and(Schedule::query()->whereJsonContains('metadata->google_event_id', 'event-page-2')->exists())->toBeTrue();
});

it('handles a cancelled event in the middle of a page without affecting other events', function (): void {
    $active1 = new GoogleEventDTO('active-1', 'A1', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false, null);
    $cancelled = new GoogleEventDTO('cancelled-1', '', Date::now(), Date::now(), false, true, null);
    $active2 = new GoogleEventDTO('active-2', 'A2', Date::parse('2026-05-01 11:00'), Date::parse('2026-05-01 12:00'), false, false, null);

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->with('consultant@workspace.com')->andReturn('fake-token');
    $client->shouldReceive('listEvents')->once()->andReturn(
        new CalendarEventsResponse(collect([$active1, $cancelled, $active2]), null, null)
    );

    buildAction($client)->handle($this->consultant);

    expect(Schedule::query()->whereJsonContains('metadata->google_event_id', 'active-1')->exists())->toBeTrue()
        ->and(Schedule::query()->whereJsonContains('metadata->google_event_id', 'active-2')->exists())->toBeTrue()
        ->and(Schedule::query()->whereJsonContains('metadata->google_event_id', 'cancelled-1')->exists())->toBeFalse();
});

it('is idempotent when run twice with the same events', function (): void {
    $event = new GoogleEventDTO('event-1', 'Meeting', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false, null);

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->with('consultant@workspace.com')->andReturn('fake-token');
    $client->shouldReceive('listEvents')->twice()->andReturn(
        new CalendarEventsResponse(collect([$event]), null, null)
    );

    $action = buildAction($client);
    $action->handle($this->consultant);
    $action->handle($this->consultant);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'event-1')
        ->count()
    )->toBe(1);
});

it('updates google_calendar_synced_at to the current time', function (): void {
    Date::setTestNow('2026-05-01 10:00:00');

    $client = Mockery::mock(GoogleCalendarClient::class);
    $client->shouldReceive('getAccessToken')->with('consultant@workspace.com')->andReturn('fake-token');
    $client->shouldReceive('listEvents')->once()->andReturn(new CalendarEventsResponse(collect([]), null, null));

    buildAction($client)->handle($this->consultant);

    expect($this->consultant->fresh()->google_calendar_synced_at->toDateTimeString())->toBe('2026-05-01 10:00:00');
});
