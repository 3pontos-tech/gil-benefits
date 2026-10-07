<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;

use function Pest\Laravel\getJson;

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
        ->assertJsonPath('data.2026-10-09 09:00:00', '09:00')
        ->assertJsonMissingPath('data.2026-10-08 09:00:00');
});

it('returns an empty object when the month has no slots', function (): void {
    $response = getJson(route('api.v1.appointments.slots', ['month' => '2026-10']))->assertOk();

    expect($response->getContent())->toBe('{"data":{}}');
});

it('caches each day for the configured seconds', function (): void {
    getJson(route('api.v1.appointments.slots', ['month' => '2026-10']))
        ->assertOk()
        ->assertJsonCount(0, 'data');

    consultantAvailableOn(Date::parse('2026-10-09'));

    getJson(route('api.v1.appointments.slots', ['month' => '2026-10']))
        ->assertOk()
        ->assertJsonCount(0, 'data');

    Cache::flush();

    getJson(route('api.v1.appointments.slots', ['month' => '2026-10']))
        ->assertOk()
        ->assertJsonPath('data.2026-10-09 09:00:00', '09:00');
});

it('validates the month format', function (string $month): void {
    getJson(route('api.v1.appointments.slots', ['month' => $month]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['month']);
})->with(['2026-13', '10/2026', '']);
