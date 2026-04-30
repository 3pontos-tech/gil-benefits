<?php

use Illuminate\Support\Facades\Date;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\UpsertBlockedScheduleAction;
use TresPontosTech\IntegrationGoogleCalendar\DTO\GoogleEventDTO;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

beforeEach(function (): void {
    $this->consultant = Consultant::factory()->create(['email' => 'consultant@workspace.com']);
    $this->action = resolve(UpsertBlockedScheduleAction::class);
});

function makeEvent(string $eventId, string $updated, ?Consultant $consultant = null): GoogleEventDTO
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
