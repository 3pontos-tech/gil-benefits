<?php

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentActiveStep;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentCompleted;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    LaravelNotification::fake();
    Event::fake();
    $this->appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();
    actingAs($this->appointment->user);
});

it('should process step to Scheduling status', function (): void {
    $sut = new AppointmentActiveStep($this->appointment);
    $sut->processStep();

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe(AppointmentStatus::Completed);
    Event::assertDispatched(AppointmentCompleted::class);
});

it('should notify user', function (): void {
    $sut = new AppointmentActiveStep($this->appointment);
    $sut->handle();

    $this->appointment->fresh();
    expect($this->appointment->status)->toBe(AppointmentStatus::Completed);
    Notification::assertNotified(__('appointments::resources.appointments.notifications.completed.title'));
});
