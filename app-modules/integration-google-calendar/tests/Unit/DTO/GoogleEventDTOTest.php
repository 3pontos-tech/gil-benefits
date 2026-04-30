<?php

use TresPontosTech\IntegrationGoogleCalendar\DTO\GoogleEventDTO;

it('parses timed event with updated field', function (): void {
    $dto = GoogleEventDTO::fromApiPayload([
        'id' => 'evt-1',
        'summary' => 'Reunião',
        'status' => 'confirmed',
        'updated' => '2026-04-29T10:00:00.000Z',
        'start' => ['dateTime' => '2026-04-30T14:00:00-03:00'],
        'end' => ['dateTime' => '2026-04-30T15:00:00-03:00'],
    ]);

    expect($dto->eventId)->toBe('evt-1')
        ->and($dto->summary)->toBe('Reunião')
        ->and($dto->isAllDay)->toBeFalse()
        ->and($dto->isCancelled)->toBeFalse()
        ->and($dto->updated)->not->toBeNull()
        ->and($dto->updated->toIso8601String())->toBe('2026-04-29T10:00:00+00:00');
});

it('parses all-day event with updated field', function (): void {
    $dto = GoogleEventDTO::fromApiPayload([
        'id' => 'evt-2',
        'summary' => 'Feriado',
        'updated' => '2026-04-29T08:30:00.000Z',
        'start' => ['date' => '2026-05-01'],
        'end' => ['date' => '2026-05-02'],
    ]);

    expect($dto->isAllDay)->toBeTrue()
        ->and($dto->updated)->not->toBeNull();
});

it('parses cancelled event with updated field', function (): void {
    $dto = GoogleEventDTO::fromApiPayload([
        'id' => 'evt-3',
        'status' => 'cancelled',
        'updated' => '2026-04-29T11:00:00.000Z',
    ]);

    expect($dto->isCancelled)->toBeTrue()
        ->and($dto->updated)->not->toBeNull();
});

it('handles event without updated field', function (): void {
    $dto = GoogleEventDTO::fromApiPayload([
        'id' => 'evt-4',
        'summary' => 'Sem updated',
        'start' => ['dateTime' => '2026-04-30T14:00:00-03:00'],
        'end' => ['dateTime' => '2026-04-30T15:00:00-03:00'],
    ]);

    expect($dto->updated)->toBeNull();
});

it('falls back to default summary when missing', function (): void {
    $dto = GoogleEventDTO::fromApiPayload([
        'id' => 'evt-5',
        'updated' => '2026-04-29T10:00:00.000Z',
        'start' => ['dateTime' => '2026-04-30T14:00:00-03:00'],
        'end' => ['dateTime' => '2026-04-30T15:00:00-03:00'],
    ]);

    expect($dto->summary)->toBe('(sem título)');
});
