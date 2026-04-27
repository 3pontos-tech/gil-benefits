<?php

use TresPontosTech\Appointments\Actions\StateMachine\AbstractAppointmentStep;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

it('Pending allows transition to Active, Cancelled and CancelledLate', function (): void {
    expect(AppointmentStatus::Pending->allowedTransitions())
        ->toContain(AppointmentStatus::Active)
        ->toContain(AppointmentStatus::Cancelled)
        ->toContain(AppointmentStatus::CancelledLate)
        ->not->toContain(AppointmentStatus::Completed);
});

it('Active allows transition to Completed, Cancelled and CancelledLate', function (): void {
    expect(AppointmentStatus::Active->allowedTransitions())
        ->toContain(AppointmentStatus::Completed)
        ->toContain(AppointmentStatus::Cancelled)
        ->toContain(AppointmentStatus::CancelledLate)
        ->not->toContain(AppointmentStatus::Pending);
});

it('Completed allows no transitions', function (): void {
    expect(AppointmentStatus::Completed->allowedTransitions())->toBeEmpty();
});

it('Cancelled allows no transitions', function (): void {
    expect(AppointmentStatus::Cancelled->allowedTransitions())->toBeEmpty();
});

it('CancelledLate allows no transitions', function (): void {
    expect(AppointmentStatus::CancelledLate->allowedTransitions())->toBeEmpty();
});

it('canTransitionTo returns true for valid transitions', function (AppointmentStatus $from, AppointmentStatus $to): void {
    expect($from->canTransitionTo($to))->toBeTrue();
})->with([
    'Pending to Active' => [AppointmentStatus::Pending, AppointmentStatus::Active],
    'Pending to Cancelled' => [AppointmentStatus::Pending, AppointmentStatus::Cancelled],
    'Pending to CancelledLate' => [AppointmentStatus::Pending, AppointmentStatus::CancelledLate],
    'Active to Completed' => [AppointmentStatus::Active,  AppointmentStatus::Completed],
    'Active to Cancelled' => [AppointmentStatus::Active,  AppointmentStatus::Cancelled],
    'Active to CancelledLate' => [AppointmentStatus::Active, AppointmentStatus::CancelledLate],
]);

it('canTransitionTo returns false for invalid transitions', function (AppointmentStatus $from, AppointmentStatus $to): void {
    expect($from->canTransitionTo($to))->toBeFalse();
})->with([
    'Pending to Completed' => [AppointmentStatus::Pending,   AppointmentStatus::Completed],
    'Active to Pending' => [AppointmentStatus::Active,    AppointmentStatus::Pending],
    'Completed to any' => [AppointmentStatus::Completed, AppointmentStatus::Pending],
    'Cancelled to any' => [AppointmentStatus::Cancelled, AppointmentStatus::Pending],
    'CancelledLate to any' => [AppointmentStatus::CancelledLate, AppointmentStatus::Pending],
]);

it('throws BadMethodCallException when calling currentStep on a terminal status', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()->withStatus($status)->create();

    expect(fn (): AbstractAppointmentStep => $status->currentStep($appointment))->toThrow(BadMethodCallException::class);
})->with([
    'Completed' => AppointmentStatus::Completed,
    'Cancelled' => AppointmentStatus::Cancelled,
    'CancelledLate' => AppointmentStatus::CancelledLate,
]);

it('creditConsuming returns only CancelledLate', function (): void {
    expect(AppointmentStatus::creditConsuming())
        ->toBe([AppointmentStatus::CancelledLate]);
});

it('creditConsuming does not include Cancelled', function (): void {
    expect(AppointmentStatus::creditConsuming())
        ->not->toContain(AppointmentStatus::Cancelled);
});
