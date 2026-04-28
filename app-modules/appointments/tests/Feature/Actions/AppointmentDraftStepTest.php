<?php

use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Appointments\Actions\StateMachine\AppointmentDraftStep;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Events\AppointmentCancelled;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    LaravelNotification::fake();
    Event::fake();
    $this->appointment = Appointment::factory()->draft()->create();
    actingAs($this->appointment->user);
});

it('should process step to pending status', function (): void {
    $sut = new AppointmentDraftStep($this->appointment);
    $sut->handle();

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe(AppointmentStatus::Pending);
});

it('should notify user', function (): void {
    $sut = new AppointmentDraftStep($this->appointment);
    $sut->handle();

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe(AppointmentStatus::Pending);
    Notification::assertNotified(__('appointments::resources.appointments.notifications.drafted.title'));
});

it('should cancel', function (): void {
    $sut = new AppointmentDraftStep($this->appointment);
    $sut->cancel();

    $this->appointment->refresh();
    expect($this->appointment->status)->toBe(AppointmentStatus::Cancelled);
    Notification::assertNotified(__('appointments::resources.appointments.notifications.cancelled.title'));
    Event::assertDispatched(AppointmentCancelled::class);
});
