<?php

declare(strict_types=1);

use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Enums\AppointmentStatus;

use function Pest\Laravel\getJson;

it('lists only the employee appointments, newest first, with pagination meta', function (): void {
    $this->travelTo('2026-10-07 10:00:00');
    $employee = actingAsApiEmployee();

    $sooner = appointmentFor($employee, AppointmentStatus::Pending, Date::parse('2026-10-10 10:00:00'));
    $later = appointmentFor($employee, AppointmentStatus::Pending, Date::parse('2026-10-12 10:00:00'));
    appointmentFor(User::factory()->employee()->create(), AppointmentStatus::Pending, Date::parse('2026-10-11 10:00:00'));

    getJson(route('api.v1.appointments.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $later->id)
        ->assertJsonPath('data.1.id', $sooner->id)
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']])
        ->assertJsonPath('meta.per_page', 50);
});

it('filters upcoming, pending and history the way the app segments them', function (): void {
    $this->travelTo('2026-10-07 10:00:00');
    $employee = actingAsApiEmployee();

    $a = appointmentFor($employee, AppointmentStatus::Pending, now()->addDays(3));
    $b = appointmentFor($employee, AppointmentStatus::Active, now()->addDays(5));
    $c = appointmentFor($employee, AppointmentStatus::Pending, now()->subDays(2));
    $d = appointmentFor($employee, AppointmentStatus::Completed, now()->subDays(10));
    $e = appointmentFor($employee, AppointmentStatus::Cancelled, now()->addDays(4));

    getJson(route('api.v1.appointments.index', ['status' => 'upcoming']))
        ->assertOk()
        ->assertJsonPath('data.*.id', [$a->id, $b->id]);

    getJson(route('api.v1.appointments.index', ['status' => 'pending']))
        ->assertOk()
        ->assertJsonPath('data.*.id', [$c->id, $a->id]);

    getJson(route('api.v1.appointments.index', ['status' => 'history']))
        ->assertOk()
        ->assertJsonPath('data.*.id', [$e->id, $c->id, $d->id]);
});

it('keeps a pending appointment from earlier today in upcoming, not in history', function (): void {
    $this->travelTo('2026-10-07 10:00:00');
    $employee = actingAsApiEmployee();

    $earlierToday = appointmentFor($employee, AppointmentStatus::Pending, Date::parse('2026-10-07 08:00:00'));

    getJson(route('api.v1.appointments.index', ['status' => 'upcoming']))
        ->assertOk()
        ->assertJsonPath('data.*.id', [$earlierToday->id]);

    getJson(route('api.v1.appointments.index', ['status' => 'history']))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

it('rejects an unknown status filter', function (): void {
    actingAsApiEmployee();

    getJson(route('api.v1.appointments.index', ['status' => 'done']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});
