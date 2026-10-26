<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Events\AppointmentReserved;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

function reservedAppointment(AppointmentStatus $status = AppointmentStatus::Pending): Appointment
{
    $user = User::factory()->create();
    $company = Company::factory()->create();

    return Appointment::factory()->withStatus($status)->create([
        'user_id' => $user->getKey(),
        'company_id' => $company->getKey(),
    ]);
}

it('spends an available credit of the company when the reservation is not covered by quota', function (): void {
    $appointment = reservedAppointment();
    $credit = UserCredit::factory()->available()->create([
        'holder_id' => $appointment->user_id,
        'company_id' => $appointment->company_id,
    ]);

    event(new AppointmentReserved($appointment, paidWithQuota: false));

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::InUse)
        ->and($credit->appointment_id)->toBe($appointment->getKey());
});

it('leaves credits alone when the reservation is covered by quota', function (): void {
    $appointment = reservedAppointment();
    $credit = UserCredit::factory()->available()->create([
        'holder_id' => $appointment->user_id,
        'company_id' => $appointment->company_id,
    ]);

    event(new AppointmentReserved($appointment, paidWithQuota: true));

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Available)
        ->and($credit->appointment_id)->toBeNull();
});

it('gives the credit back on a cancellation with notice', function (): void {
    $appointment = reservedAppointment(AppointmentStatus::Cancelled);
    $credit = UserCredit::factory()->inUse()->create(['appointment_id' => $appointment->getKey()]);

    event(new AppointmentCancelled($appointment));

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Available)
        ->and($credit->appointment_id)->toBeNull();
});

it('spends the credit on a late cancellation', function (): void {
    $appointment = reservedAppointment(AppointmentStatus::CancelledLate);
    $credit = UserCredit::factory()->inUse()->create(['appointment_id' => $appointment->getKey(), 'used_at' => null]);

    event(new AppointmentCancelled($appointment));

    expect($credit->refresh()->status)->toBe(UserCreditStatusEnum::Used)
        ->and($credit->used_at)->not->toBeNull();
});
