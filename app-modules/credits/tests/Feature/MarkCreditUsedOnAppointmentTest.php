<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Events\AppointmentCreditUsed;
use TresPontosTech\Credits\Models\UserCredit;

it('stamps the moment the credit was spent', function (): void {
    Date::setTestNow('2026-09-17 15:00:00');

    $appointment = Appointment::factory()->create();
    $credit = UserCredit::factory()->inUse()->create([
        'appointment_id' => $appointment->getKey(),
        'used_at' => null,
    ]);

    event(new AppointmentCreditUsed((string) $appointment->getKey()));

    $credit->refresh();

    expect($credit->status)->toBe(UserCreditStatusEnum::Used)
        ->and($credit->used_at?->toDateTimeString())->toBe('2026-09-17 15:00:00');
});

it('leaves a credit that was not booked on that appointment alone', function (): void {
    $appointment = Appointment::factory()->create();
    $bystander = UserCredit::factory()->available()->create(['used_at' => null]);

    event(new AppointmentCreditUsed((string) $appointment->getKey()));

    expect($bystander->fresh()->status)->toBe(UserCreditStatusEnum::Available)
        ->and($bystander->fresh()->used_at)->toBeNull();
});
