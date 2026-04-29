<?php

use Illuminate\Support\Facades\Date;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\UpsertBlockedScheduleAction;
use TresPontosTech\IntegrationGoogleCalendar\DTO\GoogleEventDTO;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

beforeEach(function (): void {
    $this->consultant = Consultant::factory()->create();
    $this->action = resolve(UpsertBlockedScheduleAction::class);
});

it('creates a blocked schedule for a same-day timed event', function (): void {
    $event = new GoogleEventDTO('evt-same-day', 'Daily Standup', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false);

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
    $event = new GoogleEventDTO('evt-allday-single', 'Day Off', Date::parse('2026-05-01'), Date::parse('2026-05-02'), true, false);

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
    $event = new GoogleEventDTO('evt-allday-multi', 'Vacation', Date::parse('2026-05-01'), Date::parse('2026-05-04'), true, false);

    $this->action->handle($this->consultant, $event);

    $schedule = Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'evt-allday-multi')
        ->firstOrFail();

    // Google all-day end date is exclusive, so effective end = May 4 - 1 = May 3
    expect($schedule->start_date->toDateString())->toBe('2026-05-01')
        ->and($schedule->end_date->toDateString())->toBe('2026-05-03');
});

it('creates a blocked schedule for a multi-day timed event', function (): void {
    $event = new GoogleEventDTO('evt-multi-timed', 'Conference', Date::parse('2026-05-01 14:00'), Date::parse('2026-05-03 18:00'), false, false);

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
    $event = new GoogleEventDTO('evt-midnight', 'Night Shift', Date::parse('2026-05-01 22:00'), Date::parse('2026-05-02 00:00'), false, false);

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

    $event = new GoogleEventDTO('evt-conflict', 'Blocked', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false);

    $this->action->handle($this->consultant, $event);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'evt-conflict')
        ->exists()
    )->toBeFalse();
});

it('replaces an existing blocked schedule when re-upserted with the same event id', function (): void {
    $event = new GoogleEventDTO('evt-idempotent', 'Meeting', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false);

    $this->action->handle($this->consultant, $event);
    $this->action->handle($this->consultant, $event);

    expect(Schedule::query()
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->whereJsonContains('metadata->google_event_id', 'evt-idempotent')
        ->count()
    )->toBe(1);
});

it('stores google_event_id and source in schedule metadata', function (): void {
    $event = new GoogleEventDTO('evt-meta', 'Meta Event', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false);

    $this->action->handle($this->consultant, $event);

    $schedule = Schedule::query()
        ->whereJsonContains('metadata->google_event_id', 'evt-meta')
        ->firstOrFail();

    expect($schedule->metadata['google_event_id'])->toBe('evt-meta')
        ->and($schedule->metadata['source'])->toBe('google-calendar');
});

it('does not affect blocked schedules of other consultants', function (): void {
    $otherConsultant = Consultant::factory()->create();
    $event = new GoogleEventDTO('evt-shared-id', 'Meeting', Date::parse('2026-05-01 09:00'), Date::parse('2026-05-01 10:00'), false, false);

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

    // Re-upsert for original consultant should not touch other consultant's schedule
    $this->action->handle($this->consultant, $event);

    expect(Schedule::query()
        ->where('schedulable_id', $otherConsultant->getKey())
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->count()
    )->toBe(1);
});
