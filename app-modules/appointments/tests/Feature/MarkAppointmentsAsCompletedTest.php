<?php

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Jobs\MarkAppointmentsAsCompleted;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Laravel\assertDatabaseHas;

it('marks active appointments more than 1 day old as completed', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subDays(2)]);

    (new MarkAppointmentsAsCompleted)->handle();

    assertDatabaseHas(Appointment::class, [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Completed,
    ]);
});

it('ignores active appointments within the 1 day buffer', function (): void {
    $appointment = Appointment::factory()
        ->withStatus(AppointmentStatus::Active)
        ->create(['appointment_at' => now()->subHours(23)]);

    (new MarkAppointmentsAsCompleted)->handle();

    assertDatabaseHas(Appointment::class, [
        'id' => $appointment->id,
        'status' => AppointmentStatus::Active,
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
    'pending' => AppointmentStatus::Pending,
    'completed' => AppointmentStatus::Completed,
    'cancelled' => AppointmentStatus::Cancelled,
    'cancelled_late' => AppointmentStatus::CancelledLate,
]);
