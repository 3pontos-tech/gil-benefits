<?php

use Illuminate\Support\Facades\Date;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\UpsertBlockedScheduleAction;
use TresPontosTech\IntegrationGoogleCalendar\DTO\GoogleEventDTO;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

beforeEach(function (): void {
    Date::setTestNow('2026-04-29 09:00:00');
    $this->consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    $this->action = resolve(UpsertBlockedScheduleAction::class);
});

afterEach(function (): void {
    Date::setTestNow();
});

function makeEvent(string $eventId, string $updated): GoogleEventDTO
{
    return new GoogleEventDTO(
        eventId: $eventId,
        summary: 'Reunião',
        start: Date::parse('2026-05-15 14:00:00'),
        end: Date::parse('2026-05-15 15:00:00'),
        isAllDay: false,
        isCancelled: false,
        updated: Date::parse($updated),
    );
}

it('creates blocked schedule for a new event', function (): void {
    $event = makeEvent('evt-new', '2026-04-29T10:00:00Z');

    $this->action->handle($this->consultant, $event);

    $schedule = Schedule::query()
        ->where('schedulable_id', $this->consultant->id)
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'evt-new')
        ->first();

    expect($schedule)->not->toBeNull()
        ->and($schedule->metadata['source'])->toBe('google-calendar')
        ->and($schedule->metadata['updated'])->toBe('2026-04-29T10:00:00+00:00');
});

it('is a no-op when event has not changed since last sync', function (): void {
    $event = makeEvent('evt-stable', '2026-04-29T10:00:00Z');

    $this->action->handle($this->consultant, $event);
    $original = Schedule::query()->whereJsonContains('metadata->google_event_id', 'evt-stable')->first();

    $this->action->handle($this->consultant, $event);
    $after = Schedule::query()->whereJsonContains('metadata->google_event_id', 'evt-stable')->first();

    expect($after->id)->toBe($original->id)
        ->and(Schedule::query()->whereJsonContains('metadata->google_event_id', 'evt-stable')->count())->toBe(1);
});

it('rebuilds blocked schedule when event updated changes', function (): void {
    $event = makeEvent('evt-updated', '2026-04-29T10:00:00Z');
    $this->action->handle($this->consultant, $event);
    $original = Schedule::query()->whereJsonContains('metadata->google_event_id', 'evt-updated')->first();

    $changed = makeEvent('evt-updated', '2026-04-29T15:00:00Z');
    $this->action->handle($this->consultant, $changed);
    $after = Schedule::query()->whereJsonContains('metadata->google_event_id', 'evt-updated')->first();

    expect($after)->not->toBeNull()
        ->and($after->id)->not->toBe($original->id)
        ->and($after->metadata['updated'])->toBe('2026-04-29T15:00:00+00:00');
});

it('removes existing blocked schedule when an appointment now overlaps', function (): void {
    $event = makeEvent('evt-overlap', '2026-04-29T10:00:00Z');
    $this->action->handle($this->consultant, $event);

    Zap::for($this->consultant)
        ->named('Conflicting appointment')
        ->appointment()
        ->allowOverlap()
        ->from('2026-05-15')
        ->to('2026-05-16')
        ->addPeriod('14:00', '15:00')
        ->save();

    $this->action->handle($this->consultant, $event);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'evt-overlap')
        ->exists())->toBeFalse();
});

it('does not create blocked schedule when overlap exists from the start', function (): void {
    Zap::for($this->consultant)
        ->named('Pre-existing appointment')
        ->appointment()
        ->from('2026-05-15')
        ->to('2026-05-16')
        ->addPeriod('14:00', '15:00')
        ->save();

    $event = makeEvent('evt-no-create', '2026-04-29T10:00:00Z');
    $this->action->handle($this->consultant, $event);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'evt-no-create')
        ->exists())->toBeFalse();
});

it('creates a blocked schedule for a same-day timed event', function (): void {
    $event = new GoogleEventDTO('evt-same-day', 'Daily Standup', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false, null);

    $this->action->handle($this->consultant, $event);

    $schedule = Schedule::query()
        ->where('schedulable_type', $this->consultant->getMorphClass())
        ->where('schedulable_id', $this->consultant->getKey())
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'evt-same-day')
        ->with('periods')
        ->firstOrFail();

    expect($schedule->name)->toBe('Daily Standup')
        ->and($schedule->start_date->toDateString())->toBe('2026-05-01')
        ->and($schedule->end_date)->toBeNull()
        ->and($schedule->periods->first()->start_time)->toBe('09:00')
        ->and($schedule->periods->first()->end_time)->toBe('10:00');
});

it('creates a blocked schedule for an all-day single-day event', function (): void {
    $event = new GoogleEventDTO('evt-allday-single', 'Day Off', Date::parse('2026-05-01'), Date::parse('2026-05-02'), true, false, null);

    $this->action->handle($this->consultant, $event);

    $schedule = Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'evt-allday-single')
        ->with('periods')
        ->firstOrFail();

    expect($schedule->start_date->toDateString())->toBe('2026-05-01')
        ->and($schedule->end_date)->toBeNull()
        ->and($schedule->periods->first()->start_time)->toBe('00:00')
        ->and($schedule->periods->first()->end_time)->toBe('23:59');
});

it('creates a blocked schedule for an all-day multi-day event', function (): void {
    $event = new GoogleEventDTO('evt-allday-multi', 'Vacation', Date::parse('2026-05-01'), Date::parse('2026-05-04'), true, false, null);

    $this->action->handle($this->consultant, $event);

    $schedule = Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'evt-allday-multi')
        ->firstOrFail();

    expect($schedule->start_date->toDateString())->toBe('2026-05-01')
        ->and($schedule->end_date->toDateString())->toBe('2026-05-03');
});

it('creates a blocked schedule for a multi-day timed event', function (): void {
    $event = new GoogleEventDTO('evt-multi-timed', 'Conference', Date::parse('2026-05-01 14:00'), Date::parse('2026-05-03 18:00'), false, false, null);

    $this->action->handle($this->consultant, $event);

    $schedule = Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'evt-multi-timed')
        ->with('periods')
        ->firstOrFail();

    expect($schedule->start_date->toDateString())->toBe('2026-05-01')
        ->and($schedule->end_date->toDateString())->toBe('2026-05-03')
        ->and($schedule->periods->first()->start_time)->toBe('00:00')
        ->and($schedule->periods->first()->end_time)->toBe('23:59');
});

it('creates a blocked schedule ending at 23:59 for a timed event ending at midnight of the next day', function (): void {
    $event = new GoogleEventDTO('evt-midnight', 'Night Shift', Date::parse('2026-05-01 22:00'), Date::parse('2026-05-02 00:00'), false, false, null);

    $this->action->handle($this->consultant, $event);

    $schedule = Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'evt-midnight')
        ->with('periods')
        ->firstOrFail();

    expect($schedule->start_date->toDateString())->toBe('2026-05-01')
        ->and($schedule->end_date)->toBeNull()
        ->and($schedule->periods->first()->start_time)->toBe('22:00')
        ->and($schedule->periods->first()->end_time)->toBe('23:59');
});

it('does not create a blocked schedule when an appointment conflicts with the event', function (): void {
    $date = '2026-05-01';

    Zap::for($this->consultant)
        ->named('Existing Appointment')
        ->appointment()
        ->from($date)
        ->to(Date::parse($date)->addDay()->toDateString())
        ->addPeriod('09:30', '10:30')
        ->save();

    $event = new GoogleEventDTO('evt-conflict', 'Blocked', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false, null);

    $this->action->handle($this->consultant, $event);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'evt-conflict')
        ->exists()
    )->toBeFalse();
});

it('replaces an existing blocked schedule when re-upserted with the same event id', function (): void {
    $event = new GoogleEventDTO('evt-idempotent', 'Meeting', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false, null);

    $this->action->handle($this->consultant, $event);
    $this->action->handle($this->consultant, $event);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'evt-idempotent')
        ->count()
    )->toBe(1);
});

it('stores google_event_id and source in schedule metadata', function (): void {
    $event = new GoogleEventDTO('evt-meta', 'Meta Event', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false, null);

    $this->action->handle($this->consultant, $event);

    $schedule = Schedule::query()
        ->whereJsonContains('metadata->google_event_id', 'evt-meta')
        ->firstOrFail();

    expect($schedule->metadata['google_event_id'])->toBe('evt-meta')
        ->and($schedule->metadata['source'])->toBe('google-calendar');
});

it('does not affect blocked schedules of other consultants', function (): void {
    $otherConsultant = Consultant::factory()->create();
    $event = new GoogleEventDTO('evt-shared-id', 'Meeting', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false, null);

    $this->action->handle($this->consultant, $event);

    Zap::for($otherConsultant)
        ->named('Other Blocked')
        ->blocked()
        ->allowOverlap()
        ->from('2026-05-01')
        ->to(null)
        ->addPeriod('09:00', '10:00')
        ->withMetadata(['google_event_id' => 'evt-other', 'source' => 'google-calendar'])
        ->save();

    $this->action->handle($this->consultant, $event);

    expect(Schedule::query()
        ->where('schedulable_id', $otherConsultant->getKey())
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->count()
    )->toBe(1);
});
