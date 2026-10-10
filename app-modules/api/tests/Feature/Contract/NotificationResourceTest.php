<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentCompletedNotification;

use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;

it('exposes every key the app reads from Notification', function (): void {
    $employee = actingAsApiEmployee();
    $appointment = Appointment::factory()->create(['user_id' => $employee->id]);
    $employee->notify(new AppointmentCompletedNotification($appointment));

    $response = getJson(route('api.v1.notifications.index'))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'type', 'data' => ['title', 'body', 'status', 'appointment_id'], 'read_at', 'created_at']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total', 'unread'],
        ])
        ->assertJsonPath('data.0.read_at', null);

    $iso = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/';

    expect($response->json('data.0.created_at'))->toMatch($iso);

    $read = patchJson(route('api.v1.notifications.read', $response->json('data.0.id')))
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'type', 'data', 'read_at', 'created_at']]);

    expect($read->json('data.read_at'))->toMatch($iso);
});
