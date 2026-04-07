<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Consultants\Models\Consultant;
use TresPontosTech\IntegrationGoogleCalendar\DTO\CreateGoogleEventDTO;

it('creates DTO from appointment with correct fields', function (): void {
    $user = User::factory()->create(['name' => 'João Silva', 'email' => 'joao@test.com']);
    $consultant = Consultant::factory()->create(['email' => 'consultant@test.com']);
    $appointmentAt = Date::parse('2026-04-10 14:00:00');

    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'consultant_id' => $consultant->id,
        'appointment_at' => $appointmentAt,
        'category_type' => AppointmentCategoryEnum::PersonalFinance,
        'status' => AppointmentStatus::Active,
        'notes' => 'Test notes',
    ]);

    $dto = CreateGoogleEventDTO::fromAppointment($appointment);

    expect($dto->summary)->toBe('Consulta - João Silva')
        ->and($dto->attendees)->toBe(['joao@test.com'])
        ->and($dto->appointmentId)->toBe($appointment->id);
});

it('generates correct Google payload structure', function (): void {
    $user = User::factory()->create(['name' => 'Test User', 'email' => 'user@test.com']);
    $consultant = Consultant::factory()->create();
    $appointmentAt = Date::parse('2026-04-10 14:00:00');

    $appointment = Appointment::factory()->create([
        'user_id' => $user->id,
        'consultant_id' => $consultant->id,
        'appointment_at' => $appointmentAt,
        'category_type' => AppointmentCategoryEnum::PersonalFinance,
        'status' => AppointmentStatus::Active,
    ]);

    $dto = CreateGoogleEventDTO::fromAppointment($appointment);
    $payload = $dto->toGooglePayload();

    expect($payload)->toHaveKeys(['summary', 'description', 'start', 'end', 'attendees', 'conferenceData'])
        ->and($payload['start'])->toHaveKeys(['dateTime', 'timeZone'])
        ->and($payload['end'])->toHaveKeys(['dateTime', 'timeZone'])
        ->and($payload['attendees'])->toBe([['email' => 'user@test.com']])
        ->and($payload['conferenceData']['createRequest']['requestId'])->toBe($appointment->id)
        ->and($payload['conferenceData']['createRequest']['conferenceSolutionKey']['type'])->toBe('hangoutsMeet');
});
