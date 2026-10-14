<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Enums\AppointmentStatus;

use function Pest\Laravel\getJson;

it('shows the employee appointment', function (): void {
    $employee = actingAsApiEmployee();
    $appointment = appointmentFor($employee, AppointmentStatus::Pending, Date::parse('2026-10-10 10:00:00'));

    getJson(route('api.v1.appointments.show', $appointment))
        ->assertOk()
        ->assertJsonPath('data.id', $appointment->id);
});

it('answers 404 for another employee appointment', function (): void {
    actingAsApiEmployee();
    $other = appointmentFor(User::factory()->employee()->create(), AppointmentStatus::Pending, Date::parse('2026-10-10 10:00:00'));

    getJson(route('api.v1.appointments.show', $other))->assertNotFound();
});

it('answers 404 for an id that is not a uuid', function (): void {
    actingAsApiEmployee();

    getJson('/api/v1/appointments/not-a-uuid')->assertNotFound();
});
