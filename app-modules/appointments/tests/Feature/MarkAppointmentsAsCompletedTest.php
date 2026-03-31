<?php

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Jobs\MarkAppointmentsAsCompleted;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Laravel\assertDatabaseHas;

it('marks active appointments with a consultant in the past as completed', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subDay()]);

    (new MarkAppointmentsAsCompleted)->handle();

    assertDatabaseHas(Appointment::class, [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Completed,
    ]);
});

it('ignores active appointments without a consultant', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->withoutConsultant()
        ->create(['appointment_at' => now()->subDay()]);

    (new MarkAppointmentsAsCompleted)->handle();

    assertDatabaseHas(Appointment::class, [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Active,
    ]);
});

it('ignores active appointments scheduled in the future', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->addDay()]);

    (new MarkAppointmentsAsCompleted)->handle();

    assertDatabaseHas(Appointment::class, [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Active,
    ]);
});

it('ignores appointments with non-active status', function (AppointmentStatus $status): void {
    $appointment = Appointment::factory()
        ->withStatus($status)
        ->create(['appointment_at' => now()->subDay()]);

    (new MarkAppointmentsAsCompleted)->handle();

    assertDatabaseHas(Appointment::class, [
        'id' => $appointment->id,
        'status' => $status,
    ]);
})->with([
    'draft' => AppointmentStatus::Draft,
    'pending' => AppointmentStatus::Pending,
    'scheduling' => AppointmentStatus::Scheduling,
    'completed' => AppointmentStatus::Completed,
    'cancelled' => AppointmentStatus::Cancelled,
]);
