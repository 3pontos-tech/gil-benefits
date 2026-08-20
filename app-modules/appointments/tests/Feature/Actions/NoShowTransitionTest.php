<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\Transitions\ActiveTransition;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Exceptions\InvalidTransitionException;
use TresPontosTech\Appointments\Exceptions\MissingTransitionDataException;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    LaravelNotification::fake();
    Mail::fake();
    Bus::fake();
    Event::fake();
});

it('sets status to NoShow when an active appointment is marked as no-show', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subHour()]);
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData(
        noShow: true,
        noShowMarkedBy: $appointment->user,
    ));

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::NoShow);
});

it('throws InvalidTransitionException when marking a pending appointment as no-show', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();

    expect(fn () => $appointment->current_transition->handle(new TransitionData(
        noShow: true,
        noShowMarkedBy: $appointment->user,
    )))->toThrow(InvalidTransitionException::class);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Pending);
});

it('throws InvalidTransitionException when marking a terminal appointment as no-show', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()->withStatus($status)->create();

    expect(fn () => $appointment->current_transition->handle(new TransitionData(
        noShow: true,
        noShowMarkedBy: $appointment->user,
    )))->toThrow(InvalidTransitionException::class);
})->with([
    'Completed' => AppointmentStatus::Completed,
    'Cancelled' => AppointmentStatus::Cancelled,
    'CancelledLate' => AppointmentStatus::CancelledLate,
    'NoShow' => AppointmentStatus::NoShow,
]);

it('throws MissingTransitionDataException when no-show has no acting user', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subHour()]);

    expect(fn () => (new ActiveTransition($appointment))->handle(new TransitionData(noShow: true)))
        ->toThrow(MissingTransitionDataException::class);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Active);
});

it('does not notify the beneficiary on no-show', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subHour()]);
    actingAs($appointment->user);

    (new ActiveTransition($appointment))->handle(new TransitionData(
        noShow: true,
        noShowMarkedBy: $appointment->user,
    ));

    Mail::assertNothingQueued();
    LaravelNotification::assertNothingSent();
});

it('no longer blocks booking after a no-show', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::NoShow)->create();

    expect($appointment->user->hasOngoingAppointment())->toBeFalse();
});
