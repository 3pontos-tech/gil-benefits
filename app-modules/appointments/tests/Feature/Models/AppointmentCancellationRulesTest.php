<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancelImpact;
use TresPontosTech\Appointments\Models\Appointment;

it('can be cancelled while pending or active and in the future', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()->withStatus($status)->make(['appointment_at' => now()->addHours(5)]);

    expect($appointment->canBeCancelled())->toBeTrue();
})->with([
    'pending' => AppointmentStatus::Pending,
    'active' => AppointmentStatus::Active,
]);

it('cannot be cancelled once past or in a terminal status', function (AppointmentStatus $status, int $hoursFromNow): void {
    $appointment = Appointment::factory()->withStatus($status)->make(['appointment_at' => now()->addHours($hoursFromNow)]);

    expect($appointment->canBeCancelled())->toBeFalse();
})->with([
    'pending in the past' => [AppointmentStatus::Pending, -1],
    'active in the past' => [AppointmentStatus::Active, -1],
    'completed' => [AppointmentStatus::Completed, 5],
    'cancelled' => [AppointmentStatus::Cancelled, 5],
    'cancelled late' => [AppointmentStatus::CancelledLate, 5],
    'no show' => [AppointmentStatus::NoShow, 5],
]);

it('tells whether cancelling now returns or loses the credit', function (): void {
    $returns = Appointment::factory()->withStatus(AppointmentStatus::Pending)->make(['appointment_at' => now()->addHours(5)]);
    $loses = Appointment::factory()->withStatus(AppointmentStatus::Pending)->make(['appointment_at' => now()->addHour()]);
    $past = Appointment::factory()->withStatus(AppointmentStatus::Pending)->make(['appointment_at' => now()->subHour()]);
    $completed = Appointment::factory()->withStatus(AppointmentStatus::Completed)->make(['appointment_at' => now()->addHours(5)]);

    expect($returns->cancelImpact())->toBe(CancelImpact::ReturnsCredit)
        ->and($loses->cancelImpact())->toBe(CancelImpact::LosesCredit)
        ->and($past->cancelImpact())->toBeNull()
        ->and($completed->cancelImpact())->toBeNull();
});
