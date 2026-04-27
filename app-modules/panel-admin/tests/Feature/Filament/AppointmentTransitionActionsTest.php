<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use TresPontosTech\Admin\Filament\Resources\Appointments\Pages\ViewAppointment;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\Jobs\CreateAppointmentCalendarEventJob;
use Zap\Facades\Zap;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    LaravelNotification::fake();
    Bus::fake();
    Mail::fake();
});

// ── Pending ─────────────────────────────────────────────────────────────────

it('shows confirm_appointment action when status is Pending', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Pending)->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getRouteKey()])
        ->assertActionVisible('confirm_appointment')
        ->assertActionHidden('complete_appointment');
});

it('shows confirm_appointment even when no consultant is assigned', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->withoutConsultant()
        ->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getRouteKey()])
        ->assertActionVisible('confirm_appointment');
});

it('confirms appointment to Active with consultant selected in modal', function (): void {
    $date = Date::now()->addDays(3)->setTime(10, 0);
    $consultant = Consultant::factory()->create();

    Zap::for($consultant)
        ->named('Availability')
        ->availability()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('08:00', '18:00')
        ->save();

    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->withoutConsultant()
        ->create(['appointment_at' => $date]);

    livewire(ViewAppointment::class, ['record' => $appointment->getRouteKey()])
        ->callAction('confirm_appointment', data: [
            'appointment_at' => $date->toDateTimeString(),
            'consultant_id' => $consultant->id,
        ])
        ->assertHasNoActionErrors();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Active);
    expect($appointment->refresh()->consultant_id)->toBe($consultant->id);
    Bus::assertDispatched(CreateAppointmentCalendarEventJob::class);
});

it('does not dispatch calendar job when appointment already has google_event_id', function (): void {
    $date = Date::now()->addDays(3)->setTime(10, 0);
    $consultant = Consultant::factory()->create();

    Zap::for($consultant)
        ->named('Availability')
        ->availability()
        ->from($date->toDateString())
        ->to($date->copy()->addDay()->toDateString())
        ->addPeriod('08:00', '18:00')
        ->save();

    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->withoutConsultant()
        ->create(['appointment_at' => $date, 'google_event_id' => 'evt-existing-123']);

    livewire(ViewAppointment::class, ['record' => $appointment->getRouteKey()])
        ->callAction('confirm_appointment', data: [
            'appointment_at' => $date->toDateTimeString(),
            'consultant_id' => $consultant->id,
        ])
        ->assertHasNoActionErrors();

    Bus::assertNotDispatched(CreateAppointmentCalendarEventJob::class);
});

// ── Active ───────────────────────────────────────────────────────────────────

it('shows complete_appointment action when status is Active', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getRouteKey()])
        ->assertActionVisible('complete_appointment')
        ->assertActionHidden('confirm_appointment');
});

it('completes appointment to Completed via action', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getRouteKey()])
        ->callAction('complete_appointment')
        ->assertHasNoActionErrors();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Completed);
});

// ── Cancel ───────────────────────────────────────────────────────────────────

it('shows cancel action for non-terminal statuses', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()->withStatus($status)->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getRouteKey()])
        ->assertActionVisible('cancel_appointment');
})->with([
    'Pending' => AppointmentStatus::Pending,
    'Active' => AppointmentStatus::Active,
]);

it('hides cancel action for terminal statuses', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()->withStatus($status)->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getRouteKey()])
        ->assertActionHidden('cancel_appointment');
})->with([
    'Completed' => AppointmentStatus::Completed,
    'Cancelled' => AppointmentStatus::Cancelled,
    'CancelledLate' => AppointmentStatus::CancelledLate,
]);

it('cancels appointment via action', function (): void {
    $appointment = Appointment::factory()->withStatus(AppointmentStatus::Active)->create();

    livewire(ViewAppointment::class, ['record' => $appointment->getRouteKey()])
        ->callAction('cancel_appointment')
        ->assertHasNoActionErrors();

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});
