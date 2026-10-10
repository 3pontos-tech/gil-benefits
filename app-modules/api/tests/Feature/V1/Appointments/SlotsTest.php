<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->travelTo('2026-10-07 10:00:00');
    actingAsApiEmployee();
    Cache::flush();
});

it('returns the bookable slots of the month from the lead day on, in the application timezone', function (): void {
    consultantAvailableOn(Date::parse('2026-10-08'), Date::parse('2026-10-09'), Date::parse('2026-10-10'));

    getJson(route('api.v1.appointments.slots', ['month' => '2026-10']))
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('data.2026-10-09T09:00:00-03:00', '09:00')
        ->assertJsonMissing(['2026-10-08T09:00:00-03:00' => '09:00']);
});

it('returns an empty object when the month has no slots', function (): void {
    $response = getJson(route('api.v1.appointments.slots', ['month' => '2026-10']))->assertOk();

    expect($response->getContent())->toBe('{"data":{}}');
});

it('caches each day for the configured seconds', function (): void {
    config(['api.slots_cache_seconds' => 60]);

    getJson(route('api.v1.appointments.slots', ['month' => '2026-10']))
        ->assertOk()
        ->assertJsonCount(0, 'data');

    consultantAvailableOn(Date::parse('2026-10-09'));

    $this->travel(59)->seconds();

    getJson(route('api.v1.appointments.slots', ['month' => '2026-10']))
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->travel(2)->seconds();

    getJson(route('api.v1.appointments.slots', ['month' => '2026-10']))
        ->assertOk()
        ->assertJsonPath('data.2026-10-09T09:00:00-03:00', '09:00');
});

it('echoes a slot key back to book it at the same instant and store the local time', function (): void {
    consultantAvailableOn(Date::parse('2026-10-09'));

    $key = array_key_first(getJson(route('api.v1.appointments.slots', ['month' => '2026-10']))->assertOk()->json('data'));

    $response = postJson(route('api.v1.appointments.store'), ['category_type' => 'personal_finance', 'appointment_at' => $key])
        ->assertCreated();

    expect(Date::parse($response->json('data.appointment_at'))->equalTo(Date::parse($key)))->toBeTrue()
        ->and(Appointment::query()->sole()->appointment_at->toDateTimeString())->toBe('2026-10-09 08:00:00');
});

it('validates the month format', function (string $month): void {
    getJson(route('api.v1.appointments.slots', ['month' => $month]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['month']);
})->with(['2026-13', '10/2026', '']);
