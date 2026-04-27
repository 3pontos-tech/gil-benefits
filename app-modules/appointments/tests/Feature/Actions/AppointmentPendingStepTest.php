<?php

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentPendingStep;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentBooked;
use TresPontosTech\Appointments\Mail\AppointmentScheduledMail;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    LaravelNotification::fake();
    Mail::fake();
    $this->appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();
    actingAs($this->appointment->user);
});

it('processes step to Active status', function (): void {
    $step = new AppointmentPendingStep($this->appointment);
    $step->processStep();

    expect($this->appointment->refresh()->status)->toBe(AppointmentStatus::Active);
});

it('fires AppointmentBooked event on processStep', function (): void {
    $step = new AppointmentPendingStep($this->appointment);

    Event::fake([AppointmentBooked::class]);

    $step->processStep();

    Event::assertDispatched(AppointmentBooked::class);
});

it('notifies user and emails consultant on handle', function (): void {
    $step = new AppointmentPendingStep($this->appointment);
    $step->handle();

    expect($this->appointment->refresh()->status)->toBe(AppointmentStatus::Active);
    Notification::assertNotified(__('appointments::resources.appointments.notifications.scheduled.title'));
    Mail::assertQueued(AppointmentScheduledMail::class);
});

it('throws when no consultant is assigned', function (): void {
    $appointment = Appointment::factory()
        ->withoutConsultant()
        ->withStatus(AppointmentStatus::Pending)
        ->create();
    actingAs($appointment->user);

    $step = new AppointmentPendingStep($appointment);

    expect(fn () => $step->handle())->toThrow(LogicException::class);
});

it('throws when appointment is not in Pending status', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();
    actingAs($appointment->user);

    $step = new AppointmentPendingStep($appointment);

    expect(fn () => $step->handle())->toThrow(LogicException::class);
});

it('does not cancel an already cancelled appointment', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Cancelled)->create();
    actingAs($appointment->user);

    $step = new AppointmentPendingStep($appointment);
    $step->cancel();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});
