<?php

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentSchedulingStep;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    LaravelNotification::fake();
    $this->appointment = Appointment::factory()->withStatus(AppointmentStatus::Scheduling)->create();
    actingAs($this->appointment->user);
});

it('should process step to Scheduling status', function (): void {
    $sut = new AppointmentSchedulingStep($this->appointment);
    $sut->processStep();

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe(AppointmentStatus::Active);
});

it('should notify user', function (): void {
    $sut = new AppointmentSchedulingStep($this->appointment);
    $sut->handle();

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe(AppointmentStatus::Active);
    Notification::assertNotified('Appointment Scheduled!');
});
