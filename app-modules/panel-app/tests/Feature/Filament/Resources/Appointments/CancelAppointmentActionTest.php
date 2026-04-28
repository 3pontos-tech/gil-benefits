<?php

declare(strict_types=1);

use TresPontosTech\App\Filament\Resources\Appointments\Pages\ListAppointments;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->employee = actingAsSubscribedEmployee();
});

it('shows cancel action on Pending appointments', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['user_id' => $this->employee->id, 'appointment_at' => now()->addDays(2)]);

    livewire(ListAppointments::class)
        ->assertTableActionVisible('cancel-appointment', $appointment);
});

it('shows cancel action on Active appointments', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['user_id' => $this->employee->id, 'appointment_at' => now()->addDays(2)]);

    livewire(ListAppointments::class)
        ->assertTableActionVisible('cancel-appointment', $appointment);
});

it('hides cancel action on Completed appointments', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Completed)
        ->create(['user_id' => $this->employee->id, 'appointment_at' => now()->subDay()]);

    livewire(ListAppointments::class)
        ->assertTableActionHidden('cancel-appointment', $appointment);
});

it('hides cancel action on Cancelled appointments', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Cancelled)
        ->create(['user_id' => $this->employee->id, 'appointment_at' => now()->addDays(2)]);

    livewire(ListAppointments::class)
        ->assertTableActionHidden('cancel-appointment', $appointment);
});

it('hides cancel action on CancelledLate appointments', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::CancelledLate)
        ->create(['user_id' => $this->employee->id, 'appointment_at' => now()->addHours(2)]);

    livewire(ListAppointments::class)
        ->assertTableActionHidden('cancel-appointment', $appointment);
});

it('hides cancel action on past appointments even if Pending or Active', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()
        ->withStatus($status)
        ->create(['user_id' => $this->employee->id, 'appointment_at' => now()->subHour()]);

    livewire(ListAppointments::class)
        ->assertTableActionHidden('cancel-appointment', $appointment);
})->with([
    'Pending' => AppointmentStatus::Pending,
    'Active' => AppointmentStatus::Active,
]);

it('sets status to Cancelled when cancelling >= 24h before appointment', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Pending)
        ->create(['user_id' => $this->employee->id, 'appointment_at' => now()->addHours(25)]);

    livewire(ListAppointments::class)
        ->callTableAction('cancel-appointment', $appointment);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::Cancelled);
});

it('sets status to CancelledLate when cancelling < 24h before appointment', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['user_id' => $this->employee->id, 'appointment_at' => now()->addHours(23)]);

    livewire(ListAppointments::class)
        ->callTableAction('cancel-appointment', $appointment);

    expect($appointment->refresh()->status)->toBe(AppointmentStatus::CancelledLate);
});
