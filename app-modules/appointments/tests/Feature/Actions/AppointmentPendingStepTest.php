<?php

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentPendingStep;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    LaravelNotification::fake();
    $this->appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();
    actingAs($this->appointment->user);
});

it('should process step to Scheduling status', function (): void {
    $sut = new AppointmentPendingStep($this->appointment);
    $sut->processStep();

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe(AppointmentStatus::Scheduling);
});

it('should notify user', function (): void {
    $sut = new AppointmentPendingStep($this->appointment);
    $sut->handle();

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe(AppointmentStatus::Scheduling);
    Notification::assertNotified('Appointment under Scheduling');
});
