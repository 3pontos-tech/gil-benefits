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
