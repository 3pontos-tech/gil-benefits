<?php

use TresPontosTech\Appointments\Actions\Transitions\ActiveTransition;
use TresPontosTech\Appointments\Actions\Transitions\CancelledLateTransition;
use TresPontosTech\Appointments\Actions\Transitions\CancelledTransition;
use TresPontosTech\Appointments\Actions\Transitions\CompletedTransition;
use TresPontosTech\Appointments\Actions\Transitions\PendingTransition;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Models\Appointment;

// --- transition() ---

it('Pending resolves to PendingTransition', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();

    expect(AppointmentStatus::Pending->transition($appointment))->toBeInstanceOf(PendingTransition::class);
});

it('Active resolves to ActiveTransition', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();

    expect(AppointmentStatus::Active->transition($appointment))->toBeInstanceOf(ActiveTransition::class);
});

it('Completed resolves to CompletedTransition', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create();

    expect(AppointmentStatus::Completed->transition($appointment))->toBeInstanceOf(CompletedTransition::class);
});

it('Cancelled resolves to CancelledTransition', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->create();

    expect(AppointmentStatus::Cancelled->transition($appointment))->toBeInstanceOf(CancelledTransition::class);
});

it('CancelledLate resolves to CancelledLateTransition', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::CancelledLate)->create();

    expect(AppointmentStatus::CancelledLate->transition($appointment))->toBeInstanceOf(CancelledLateTransition::class);
});

// --- canChange() ---

it('non-terminal statuses can change', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()->withStatus($status)->create();

    expect($status->transition($appointment)->canChange())->toBeTrue();
})->with([
    'Pending' => AppointmentStatus::Pending,
    'Active' => AppointmentStatus::Active,
]);

it('terminal statuses cannot change', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()->withStatus($status)->create();

    expect($status->transition($appointment)->canChange())->toBeFalse();
})->with([
    'Completed' => AppointmentStatus::Completed,
    'Cancelled' => AppointmentStatus::Cancelled,
    'CancelledLate' => AppointmentStatus::CancelledLate,
]);

// --- choices() ---

it('Pending choices include Active, Cancelled and CancelledLate', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();

    expect(AppointmentStatus::Pending->transition($appointment)->choices())
        ->toContain(AppointmentStatus::Active)
        ->toContain(AppointmentStatus::Cancelled)
        ->toContain(AppointmentStatus::CancelledLate)
        ->not->toContain(AppointmentStatus::Completed);
});

it('Active choices include Completed, Cancelled and CancelledLate', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();

    expect(AppointmentStatus::Active->transition($appointment)->choices())
        ->toContain(AppointmentStatus::Completed)
        ->toContain(AppointmentStatus::Cancelled)
        ->toContain(AppointmentStatus::CancelledLate)
        ->not->toContain(AppointmentStatus::Pending);
});

it('terminal statuses have empty choices', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()->withStatus($status)->create();

    expect($status->transition($appointment)->choices())->toBeEmpty();
})->with([
    'Completed' => AppointmentStatus::Completed,
    'Cancelled' => AppointmentStatus::Cancelled,
    'CancelledLate' => AppointmentStatus::CancelledLate,
]);

// --- resolveCancellationStatus() ---

it('resolves to Cancelled for Admin actor regardless of time', function (): void {
    $appointment = Appointment::factory()->create(['appointment_at' => now()->addHours(1)]);

    expect(AppointmentStatus::resolveCancellationStatus($appointment, CancellationActor::Admin))
        ->toBe(AppointmentStatus::Cancelled);
});

it('resolves to Cancelled for User actor >= 24h before appointment', function (): void {
    $appointment = Appointment::factory()->create(['appointment_at' => now()->addHours(25)]);

    expect(AppointmentStatus::resolveCancellationStatus($appointment, CancellationActor::User))
        ->toBe(AppointmentStatus::Cancelled);
});

it('resolves to CancelledLate for User actor < 24h before appointment', function (): void {
    $appointment = Appointment::factory()->create(['appointment_at' => now()->addHours(23)]);

    expect(AppointmentStatus::resolveCancellationStatus($appointment, CancellationActor::User))
        ->toBe(AppointmentStatus::CancelledLate);
});
