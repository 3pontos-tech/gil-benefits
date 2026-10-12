<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Support\BookableSlots;

beforeEach(function (): void {
    $this->travelTo('2026-10-07 10:00:00');
});

it('lists no slots before the booking lead even with availability', function (): void {
    consultantAvailableOn(Date::parse('2026-10-08'));

    expect(resolve(BookableSlots::class)->forDay(Date::parse('2026-10-08')))->toBe([]);
});

it('lists the union of the consultants slots from the lead day on', function (): void {
    consultantAvailableOn(Date::parse('2026-10-09'));

    $slots = resolve(BookableSlots::class)->forDay(Date::parse('2026-10-09'));

    expect($slots)->toHaveKey('2026-10-09 09:00:00', '09:00')
        ->and($slots)->toHaveCount(10);
});

it('recognises a slot sent in another offset once normalised to the app timezone', function (): void {
    consultantAvailableOn(Date::parse('2026-10-09'));

    $bookableSlots = resolve(BookableSlots::class);
    $parsed = $bookableSlots->parse('2026-10-09T12:00:00.000Z');

    expect($parsed?->toDateTimeString())->toBe('2026-10-09 09:00:00')
        ->and($parsed?->tzName)->toBe('America/Sao_Paulo')
        ->and($bookableSlots->contains(Date::parse('2026-10-09T12:00:00Z')))->toBeTrue();
});

it('parses garbage, blanks and unavailable times as null', function (?string $value): void {
    consultantAvailableOn(Date::parse('2026-10-09'));

    expect(resolve(BookableSlots::class)->parse($value))->toBeNull();
})->with([
    'null' => [null],
    'empty' => [''],
    'garbage' => ['not-a-datetime'],
    'outside availability' => ['2026-10-09 03:00:00'],
    'before the lead' => ['2026-10-08 09:00:00'],
]);

describe('booking horizon (60 days)', function (): void {
    beforeEach(function (): void {
        $this->travelTo('2026-10-07 10:00:00');
    });

    it('ends the window 60 days from today', function (): void {
        expect(resolve(BookableSlots::class)->lastBookableDay()->toDateTimeString())->toBe('2026-12-06 00:00:00');
    });

    it('offers the last day of the window and nothing after it', function (): void {
        consultantAvailableOn(Date::parse('2026-12-06'), Date::parse('2026-12-07'));
        $slots = resolve(BookableSlots::class);

        expect($slots->forDay(Date::parse('2026-12-06')))->not->toBeEmpty()
            ->and($slots->forDay(Date::parse('2026-12-07')))->toBe([])
            ->and($slots->contains(Date::parse('2026-12-06 09:00:00')))->toBeTrue()
            ->and($slots->contains(Date::parse('2026-12-07 09:00:00')))->toBeFalse()
            ->and($slots->parse('2026-12-07T09:00:00-03:00'))->toBeNull();
    });

    it('does not ask the consultants agenda for a day outside the window', function (string $day): void {
        forbidAgendaLookups();

        expect(resolve(BookableSlots::class)->forDay(Date::parse($day)))->toBe([]);
    })->with(['day after the window' => '2026-12-07', 'far future' => '9999-12-01', 'before the lead time' => '2026-10-08']);
});
