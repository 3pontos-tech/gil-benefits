<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\AppointmentFeedback;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->travelTo('2026-10-07 10:00:00');
    $this->employee = actingAsApiEmployee();
    $this->appointment = appointmentFor($this->employee, AppointmentStatus::Completed, now()->subDays(2));
});

it('stores the rating and comment and answers the appointment with feedback', function (): void {
    postJson(route('api.v1.appointments.feedback.store', $this->appointment), ['rating' => 5, 'comment' => 'Direto ao ponto.'])
        ->assertCreated()
        ->assertJsonPath('data.feedback', ['rating' => 5, 'comment' => 'Direto ao ponto.']);

    assertDatabaseHas(AppointmentFeedback::class, [
        'appointment_id' => $this->appointment->id,
        'user_id' => $this->employee->id,
        'rating' => 5,
    ]);
});

it('stores a blank comment as null', function (): void {
    postJson(route('api.v1.appointments.feedback.store', $this->appointment), ['rating' => 4, 'comment' => ''])
        ->assertCreated()
        ->assertJsonPath('data.feedback.comment', null);
});

it('refuses when the appointment is not completed', function (): void {
    $active = appointmentFor($this->employee, AppointmentStatus::Active, now()->addDay());

    postJson(route('api.v1.appointments.feedback.store', $active), ['rating' => 5])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['appointment' => 'Só é possível avaliar uma consultoria concluída.']);
});

it('refuses a second feedback', function (): void {
    AppointmentFeedback::factory()->create(['appointment_id' => $this->appointment->id, 'user_id' => $this->employee->id]);

    postJson(route('api.v1.appointments.feedback.store', $this->appointment), ['rating' => 5])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['appointment' => 'Esta consultoria já foi avaliada.']);

    expect(AppointmentFeedback::query()->count())->toBe(1);
});

it('validates rating and comment', function (string $field, mixed $value): void {
    postJson(route('api.v1.appointments.feedback.store', $this->appointment), ['rating' => 5, $field => $value])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'rating zero' => ['rating', 0],
    'rating six' => ['rating', 6],
    'rating text' => ['rating', 'five'],
    'comment too long' => ['comment', str_repeat('a', 1001)],
]);

it('answers 404 for another employee appointment', function (): void {
    $other = appointmentFor(User::factory()->employee()->create(), AppointmentStatus::Completed, now()->subDays(2));

    postJson(route('api.v1.appointments.feedback.store', $other), ['rating' => 5])->assertNotFound();
});
