<?php

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentActiveStep;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    LaravelNotification::fake();
    $this->appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();
    actingAs($this->appointment->user);
});

it('should process step to Scheduling status', function (): void {
    $step = new AppointmentActiveStep($this->appointment);
    $step->processStep();

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe(AppointmentStatus::Completed);
});

it('should notify user', function (): void {
    $step = new AppointmentActiveStep($this->appointment);
    $step->handle();

    $this->appointment->fresh();
    expect($this->appointment->status)->toBe(AppointmentStatus::Completed);
    Notification::assertNotified(__('appointments::resources.appointments.notifications.completed.title'));
});

it('throws when appointment is not in Active status', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();
    actingAs($appointment->user);

    $step = new AppointmentActiveStep($appointment);

    expect(fn () => $step->handle())->toThrow(LogicException::class);
});

it('does not cancel a completed appointment', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Completed)->create();
    actingAs($appointment->user);

    $step = new AppointmentActiveStep($appointment);
    $step->cancel();

    $appointment->refresh();
    expect($appointment->status)->toBe(AppointmentStatus::Completed);
});
