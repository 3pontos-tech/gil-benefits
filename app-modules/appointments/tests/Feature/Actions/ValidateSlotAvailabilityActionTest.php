<?php

use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Actions\ValidateSlotAvailabilityAction;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Consultants\Models\Consultant;
use Zap\Facades\Zap;

it('returns available consultants when slot is free', function (): void {
    $consultant = Consultant::factory()->create();
    $date = Date::now()->addDays(3);

    Zap::for($consultant)
        ->named('Availability')
        ->availability()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('08:00', '18:00')
        ->save();

    $action = resolve(ValidateSlotAvailabilityAction::class);
    $result = $action->handle($date->copy()->setTime(10, 0));

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($consultant->id);
});

it('throws SlotUnavailableException when no consultant is available', function (): void {
    $date = Date::now()->addDays(3);

    $action = resolve(ValidateSlotAvailabilityAction::class);
    $action->handle($date->copy()->setTime(10, 0));
})->throws(SlotUnavailableException::class);

it('throws SlotUnavailableException when slot is already booked', function (): void {
    $consultant = Consultant::factory()->create();
    $date = Date::now()->addDays(3);

    Zap::for($consultant)
        ->named('Availability')
        ->availability()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('08:00', '18:00')
        ->save();

    Zap::for($consultant)
        ->named('Existing Appointment')
        ->appointment()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('10:00', '11:00')
        ->save();

    $action = resolve(ValidateSlotAvailabilityAction::class);
    $action->handle($date->copy()->setTime(10, 0));
})->throws(SlotUnavailableException::class);
