<?php

use Illuminate\Support\Facades\Date;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\RemoveCancelledGoogleEventAction;
use TresPontosTech\IntegrationGoogleCalendar\Actions\RemoveStaleBlockedSchedulesAction;
use TresPontosTech\IntegrationGoogleCalendar\Actions\SyncConsultantCalendarAction;
use TresPontosTech\IntegrationGoogleCalendar\Actions\UpsertBlockedScheduleAction;
use TresPontosTech\IntegrationGoogleCalendar\DTO\GoogleEventDTO;
use TresPontosTech\IntegrationGoogleCalendar\GoogleCalendarClient;
use TresPontosTech\IntegrationGoogleCalendar\Responses\CalendarEventsResponse;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

beforeEach(function (): void {
    $this->consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);

    $this->client = Mockery::mock(GoogleCalendarClient::class);
    $this->client->shouldReceive('getAccessToken')->with('consultant@workspace.com')->andReturn('fake-token');

    $this->action = new SyncConsultantCalendarAction(
        $this->client,
        new UpsertBlockedScheduleAction,
        new RemoveCancelledGoogleEventAction,
        new RemoveStaleBlockedSchedulesAction,
    );
});

it('creates a blocked schedule for each active event returned', function (): void {
    $event = new GoogleEventDTO('event-1', 'Meeting', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false);

    $this->client->shouldReceive('listEvents')->once()->andReturn(
        new CalendarEventsResponse(collect([$event]), null)
    );

    $this->action->handle($this->consultant);

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

    $cancelled = new GoogleEventDTO('event-cancelled', '', Date::now(), Date::now(), false, true);

    $this->client->shouldReceive('listEvents')->once()->andReturn(
        new CalendarEventsResponse(collect([$cancelled]), null)
    );

    $this->action->handle($this->consultant);

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

    $freshEvent = new GoogleEventDTO('fresh-event', 'Fresh Meeting', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false);

    $this->client->shouldReceive('listEvents')->once()->andReturn(
        new CalendarEventsResponse(collect([$freshEvent]), null)
    );

    $this->action->handle($this->consultant);

    expect(Schedule::query()->whereJsonContains('metadata->google_event_id', 'stale-event')->exists())->toBeFalse()
        ->and(Schedule::query()->whereJsonContains('metadata->google_event_id', 'fresh-event')->exists())->toBeTrue();
});

it('paginates through all pages collecting events from each', function (): void {
    $eventPage1 = new GoogleEventDTO('event-page-1', 'E1', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false);
    $eventPage2 = new GoogleEventDTO('event-page-2', 'E2', Date::parse('2026-05-02 09:00'), Date::parse('2026-05-02 10:00'), false, false);

    $this->client->shouldReceive('listEvents')
        ->once()
        ->with('fake-token', 'primary', Mockery::any(), Mockery::any(), null)
        ->andReturn(new CalendarEventsResponse(collect([$eventPage1]), 'page-token-2'));

    $this->client->shouldReceive('listEvents')
        ->once()
        ->with('fake-token', 'primary', Mockery::any(), Mockery::any(), 'page-token-2')
        ->andReturn(new CalendarEventsResponse(collect([$eventPage2]), null));

    $this->action->handle($this->consultant);

    expect(Schedule::query()->whereJsonContains('metadata->google_event_id', 'event-page-1')->exists())->toBeTrue()
        ->and(Schedule::query()->whereJsonContains('metadata->google_event_id', 'event-page-2')->exists())->toBeTrue();
});

it('handles a cancelled event in the middle of a page without affecting other events', function (): void {
    $active1 = new GoogleEventDTO('active-1', 'A1', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false);
    $cancelled = new GoogleEventDTO('cancelled-1', '', Date::now(), Date::now(), false, true);
    $active2 = new GoogleEventDTO('active-2', 'A2', Date::parse('2026-05-01 11:00'), Date::parse('2026-05-01 12:00'), false, false);

    $this->client->shouldReceive('listEvents')->once()->andReturn(
        new CalendarEventsResponse(collect([$active1, $cancelled, $active2]), null)
    );

    $this->action->handle($this->consultant);

    expect(Schedule::query()->whereJsonContains('metadata->google_event_id', 'active-1')->exists())->toBeTrue()
        ->and(Schedule::query()->whereJsonContains('metadata->google_event_id', 'active-2')->exists())->toBeTrue()
        ->and(Schedule::query()->whereJsonContains('metadata->google_event_id', 'cancelled-1')->exists())->toBeFalse();
});

it('is idempotent when run twice with the same events', function (): void {
    $event = new GoogleEventDTO('event-1', 'Meeting', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false);

    $this->client->shouldReceive('listEvents')->twice()->andReturn(
        new CalendarEventsResponse(collect([$event]), null)
    );

    $this->action->handle($this->consultant);
    $this->action->handle($this->consultant);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'event-1')
        ->count()
    )->toBe(1);
});

it('updates google_calendar_synced_at to the current time', function (): void {
    Date::setTestNow('2026-05-01 10:00:00');

    $this->client->shouldReceive('listEvents')->once()->andReturn(new CalendarEventsResponse(collect([]), null));

    $this->action->handle($this->consultant);

    expect($this->consultant->fresh()->google_calendar_synced_at->toDateTimeString())->toBe('2026-05-01 10:00:00');
});
