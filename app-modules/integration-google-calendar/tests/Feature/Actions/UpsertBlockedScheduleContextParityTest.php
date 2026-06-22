<?php

use Illuminate\Support\Facades\Date;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Actions\UpsertBlockedScheduleAction;
use TresPontosTech\IntegrationGoogleCalendar\DTO\GoogleEventDTO;
use TresPontosTech\IntegrationGoogleCalendar\Support\ConsultantSyncContext;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

beforeEach(function (): void {
    Date::setTestNow('2026-04-29 09:00:00');
});

afterEach(function (): void {
    Date::setTestNow();
});

function parityEvent(
    string $id,
    string $start,
    string $end,
    bool $isAllDay = false,
    ?string $updated = '2026-04-29T10:00:00Z',
): GoogleEventDTO {
    return new GoogleEventDTO(
        eventId: $id,
        summary: 'Reunião',
        start: Date::parse($start),
        end: Date::parse($end),
        isAllDay: $isAllDay,
        isCancelled: false,
        updated: $updated === null ? null : Date::parse($updated),
    );
}

function createAppointment(Consultant $consultant, string $from, string $to, string $startTime, string $endTime): void
{
    Zap::for($consultant)
        ->named('Appointment')
        ->appointment()
        ->from($from)
        ->to($to)
        ->addPeriod($startTime, $endTime)
        ->save();
}

function createExistingBlock(Consultant $consultant, string $eventId, string $updated): void
{
    Zap::for($consultant)
        ->named('Existing block')
        ->blocked()
        ->allowOverlap()
        ->from('2026-05-15')
        ->to(null)
        ->addPeriod('14:00', '15:00')
        ->withMetadata(['google_event_id' => $eventId, 'source' => 'google-calendar', 'updated' => $updated])
        ->save();
}

/**
 * @return array<int, array<string, mixed>>
 */
function blockSnapshot(Consultant $consultant): array
{
    return Schedule::query()
        ->where('schedulable_id', $consultant->getKey())
        ->where('schedule_type', ScheduleTypes::BLOCKED)
        ->with('periods')
        ->get()
        ->map(fn (Schedule $schedule): array => [
            'name' => $schedule->name,
            'start_date' => $schedule->start_date->toDateString(),
            'end_date' => $schedule->end_date?->toDateString(),
            'event_id' => $schedule->metadata['google_event_id'] ?? null,
            'updated' => $schedule->metadata['updated'] ?? null,
            'periods' => $schedule->periods
                ->map(fn (object $period): string => sprintf('%s-%s', $period->start_time, $period->end_time))
                ->sort()->values()->all(),
        ])
        ->sortBy('event_id')->values()->all();
}

function assertContextParity(Closure $setup, GoogleEventDTO $event): void
{
    $action = resolve(UpsertBlockedScheduleAction::class);

    $withoutContext = Consultant::factory()->create();
    $setup($withoutContext);
    $action->handle($withoutContext, $event);

    $withContext = Consultant::factory()->create();
    $setup($withContext);
    $action->handle($withContext, $event, ConsultantSyncContext::for($withContext));

    expect(blockSnapshot($withContext))
        ->not->toBeEmpty()
        ->toEqual(blockSnapshot($withoutContext));
}

it('parity: new event with no overlapping appointment creates the block', function (): void {
    assertContextParity(
        fn (Consultant $c) => createAppointment($c, '2026-05-15', '2026-05-16', '08:00', '09:00'),
        parityEvent('evt-no-overlap', '2026-05-15 14:00', '2026-05-15 15:00'),
    );
});

it('parity: an overlapping appointment suppresses the block', function (): void {
    $action = resolve(UpsertBlockedScheduleAction::class);
    $event = parityEvent('evt-overlap', '2026-05-15 14:00', '2026-05-15 15:00');

    $withoutContext = Consultant::factory()->create();
    createAppointment($withoutContext, '2026-05-15', '2026-05-16', '14:00', '15:30');
    $action->handle($withoutContext, $event);

    $withContext = Consultant::factory()->create();
    createAppointment($withContext, '2026-05-15', '2026-05-16', '14:00', '15:30');
    $action->handle($withContext, $event, ConsultantSyncContext::for($withContext));

    // Both must agree the slot is busy and create no block.
    expect(blockSnapshot($withoutContext))->toBeEmpty()
        ->and(blockSnapshot($withContext))->toEqual(blockSnapshot($withoutContext));
});

it('parity: an appointment touching the boundary does not overlap', function (): void {
    assertContextParity(
        fn (Consultant $c) => createAppointment($c, '2026-05-15', '2026-05-16', '13:00', '14:00'),
        parityEvent('evt-touch', '2026-05-15 14:00', '2026-05-15 15:00'),
    );
});

it('parity: an appointment with a null end date never overlaps', function (): void {
    assertContextParity(
        function (Consultant $c): void {
            Zap::for($c)
                ->named('Open appointment')
                ->appointment()
                ->from('2026-05-15')
                ->to(null)
                ->addPeriod('14:00', '15:00')
                ->save();
        },
        parityEvent('evt-null-end', '2026-05-15 14:00', '2026-05-15 15:00'),
    );
});

it('parity: all-day events', function (): void {
    // Appointment on a different day so the all-day block is actually created.
    assertContextParity(
        fn (Consultant $c) => createAppointment($c, '2026-05-20', '2026-05-21', '08:00', '09:00'),
        parityEvent('evt-all-day', '2026-05-15 00:00', '2026-05-16 00:00', isAllDay: true),
    );
});

it('parity: multi-day events', function (): void {
    assertContextParity(
        fn (Consultant $c) => createAppointment($c, '2026-05-20', '2026-05-21', '08:00', '09:00'),
        parityEvent('evt-multi-day', '2026-05-15 10:00', '2026-05-17 11:00'),
    );
});

it('parity: an unchanged existing block is left untouched', function (): void {
    assertContextParity(
        fn (Consultant $c) => createExistingBlock($c, 'evt-stable', '2026-04-29T10:00:00+00:00'),
        parityEvent('evt-stable', '2026-05-15 14:00', '2026-05-15 15:00'),
    );
});

it('parity: a changed existing block is recreated', function (): void {
    assertContextParity(
        fn (Consultant $c) => createExistingBlock($c, 'evt-changed', '2026-01-01T00:00:00+00:00'),
        parityEvent('evt-changed', '2026-05-15 14:00', '2026-05-15 15:00'),
    );
});

it('parity: an overlapping appointment deletes a stale existing block', function (): void {
    $action = resolve(UpsertBlockedScheduleAction::class);
    $event = parityEvent('evt-delete', '2026-05-15 14:00', '2026-05-15 15:00');

    $setup = function (Consultant $c): void {
        createAppointment($c, '2026-05-15', '2026-05-16', '14:00', '15:30');
        createExistingBlock($c, 'evt-delete', '2026-01-01T00:00:00+00:00');
    };

    $withoutContext = Consultant::factory()->create();
    $setup($withoutContext);
    $action->handle($withoutContext, $event);

    $withContext = Consultant::factory()->create();
    $setup($withContext);
    $action->handle($withContext, $event, ConsultantSyncContext::for($withContext));

    expect(blockSnapshot($withoutContext))->toBeEmpty()
        ->and(blockSnapshot($withContext))->toEqual(blockSnapshot($withoutContext));
});

it('parity: a block recorded under a non-google source is still detected', function (): void {
    // Guards the dropped source filter: the per-event lookup matched any BLOCKED
    // schedule with the event id regardless of source, so the context must too.
    assertContextParity(
        function (Consultant $c): void {
            Zap::for($c)
                ->named('Legacy block')
                ->blocked()
                ->allowOverlap()
                ->from('2026-05-15')
                ->to(null)
                ->addPeriod('14:00', '15:00')
                ->withMetadata(['google_event_id' => 'evt-legacy', 'source' => 'legacy-import', 'updated' => '2026-04-29T10:00:00+00:00'])
                ->save();
        },
        parityEvent('evt-legacy', '2026-05-15 14:00', '2026-05-15 15:00'),
    );
});
