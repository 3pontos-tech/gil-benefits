<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;

use function Pest\Laravel\getJson;

it('exposes every key the app reads from Credits', function (): void {
    $employee = actingAsApiEmployee();
    $appointment = appointmentFor($employee, AppointmentStatus::Completed, now()->subDays(3));

    standaloneCreditFor($employee, [
        'status' => UserCreditStatusEnum::Used,
        'used_at' => now()->subDays(3),
        'appointment_id' => $appointment->id,
        'expires_at' => now()->addMonth(),
    ]);

    $response = getJson(route('api.v1.credits.show'))
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'monthly_quota' => ['limit', 'left', 'renews_at'],
            'credits' => [['id', 'status', 'owner_type', 'expires_at', 'used_at', 'appointment_id', 'grant_id', 'created_at', 'updated_at']],
        ]])
        ->assertJsonPath('data.credits.0.appointment_id', $appointment->id)
        ->assertJsonPath('data.credits.0.grant_id', null);

    $iso = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/';

    expect($response->json('data.monthly_quota.renews_at'))->toMatch('/^\d{4}-\d{2}-\d{2}$/')
        ->and($response->json('data.credits.0.expires_at'))->toMatch($iso)
        ->and($response->json('data.credits.0.used_at'))->toMatch($iso)
        ->and($response->json('data.credits.0.created_at'))->toMatch($iso);
});
