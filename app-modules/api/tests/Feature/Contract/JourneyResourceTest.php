<?php

declare(strict_types=1);

use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;

use function Pest\Laravel\getJson;

it('exposes every key the app reads from Journey', function (): void {
    $employee = actingAsApiEmployee();
    Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $employee->id,
        'category_type' => AppointmentCategoryEnum::PersonalFinance,
        'appointment_at' => now()->subDay(),
    ]);

    $response = getJson(route('api.v1.me.journey'))
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'stage', 'stage_index', 'stages', 'completed_consultations', 'topics_covered', 'topics_total',
            'ratings_given', 'pending_ratings', 'last_consultation_at', 'completed_this_month',
            'health_score', 'health_score_previous_month',
            'quarter' => ['completed', 'goal', 'starts_at', 'ends_at'],
            'health_history' => [['month', 'score']],
            'focus' => [['label', 'status_label', 'tone']],
        ]])
        ->assertJsonPath('data.stages', ['endebted', 'messy', 'payer', 'saver', 'investor'])
        ->assertJsonPath('data.topics_covered', ['personal_finance'])
        ->assertJsonPath('data.topics_total', 6)
        ->assertJsonCount(6, 'data.health_history')
        ->assertJsonCount(3, 'data.focus');

    expect($response->json('data.last_consultation_at'))->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/')
        ->and($response->json('data.health_history.5.score'))->toBe($response->json('data.health_score'));
});
