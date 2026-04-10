<?php

use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Actions\GetAvailableConsultantsAction;
use TresPontosTech\Consultants\Models\Consultant;
use Zap\Facades\Zap;

beforeEach(function (): void {
    $this->date = Date::now()->addDays(3);
});

function makeAvailableConsultant($date, string $name = 'Consultant'): Consultant
{
    $consultant = Consultant::factory()->create(['name' => $name]);

    Zap::for($consultant)
        ->named('Availability')
        ->availability()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('08:00', '18:00')
        ->save();

    return $consultant;
}

function blockConsultant($consultant, $date, string $startTime, string $endTime): void
{
    Zap::for($consultant)
        ->named('Existing Appointment')
        ->appointment()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod($startTime, $endTime)
        ->save();
}

it('returns only consultants available for the slot', function (): void {
    $available = makeAvailableConsultant($this->date, 'Available');
    $busy = makeAvailableConsultant($this->date, 'Busy');

    blockConsultant($busy, $this->date, '10:00', '11:00');

    $result = resolve(GetAvailableConsultantsAction::class)
        ->handle($this->date->copy()->setTime(10, 0));

    expect($result)->toHaveCount(1)
        ->and($result->first()->getKey())->toBe($available->getKey());
});

it('returns empty collection when no consultant has availability at the slot', function (): void {
    makeAvailableConsultant($this->date, 'A');
    makeAvailableConsultant($this->date, 'B');

    $result = resolve(GetAvailableConsultantsAction::class)
        ->handle($this->date->copy()->setTime(22, 0));

    expect($result)->toHaveCount(0);
});

it('always includes the given consultant even when they have a conflict', function (): void {
    $current = makeAvailableConsultant($this->date, 'Current');
    blockConsultant($current, $this->date, '10:00', '11:00');

    $result = resolve(GetAvailableConsultantsAction::class)
        ->handle(
            appointmentAt: $this->date->copy()->setTime(10, 0),
            alwaysIncludeConsultantId: $current->getKey(),
        );

    expect($result->pluck('id'))->toContain($current->getKey());
});

it('excludes consultants with partial overlaps on the requested slot', function (int $existingStart, int $existingEnd): void {
    $busy = makeAvailableConsultant($this->date, 'Busy');
    blockConsultant(
        $busy,
        $this->date,
        sprintf('%02d:00', $existingStart),
        sprintf('%02d:00', $existingEnd),
    );

    $result = resolve(GetAvailableConsultantsAction::class)
        ->handle($this->date->copy()->setTime(10, 0));

    expect($result->pluck('id'))->not->toContain($busy->getKey());
})->with([
    'starts before, ends inside' => [9, 11],
    'starts inside, ends after' => [10, 12],
    'wraps requested slot' => [9, 12],
    'exact overlap' => [10, 11],
]);

it('includes consultants whose existing appointment is adjacent to the slot', function (int $existingStart, int $existingEnd): void {
    $consultant = makeAvailableConsultant($this->date, 'Adjacent');
    blockConsultant(
        $consultant,
        $this->date,
        sprintf('%02d:00', $existingStart),
        sprintf('%02d:00', $existingEnd),
    );

    $result = resolve(GetAvailableConsultantsAction::class)
        ->handle($this->date->copy()->setTime(10, 0));

    expect($result->pluck('id'))->toContain($consultant->getKey());
})->with([
    'ends when slot starts' => [9, 10],
    'starts when slot ends' => [11, 12],
]);
