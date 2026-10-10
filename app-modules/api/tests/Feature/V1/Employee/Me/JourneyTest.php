<?php

declare(strict_types=1);

use App\Models\Users\User;
use Laravel\Sanctum\Sanctum;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Models\AppointmentFeedback;
use TresPontosTech\User\Enums\LifeMoment;
use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Laravel\getJson;

/**
 * @param  array<string, mixed>  $attributes
 */
function completedAppointmentFor(User $user, array $attributes = []): Appointment
{
    return Appointment::factory()->withStatus(AppointmentStatus::Completed)->create([
        'user_id' => $user->id,
        ...$attributes,
    ]);
}

it('counts the completed meetings of the current calendar quarter', function (): void {
    $this->travelTo('2026-08-15 12:00:00');
    $employee = actingAsApiEmployee();

    completedAppointmentFor($employee, ['appointment_at' => '2026-07-01 00:30:00']);
    completedAppointmentFor($employee, ['appointment_at' => '2026-09-30 23:00:00']);
    completedAppointmentFor($employee, ['appointment_at' => '2026-06-30 23:00:00']);
    Appointment::factory()->withStatus(AppointmentStatus::Pending)->create([
        'user_id' => $employee->id,
        'appointment_at' => '2026-08-20 10:00:00',
    ]);

    getJson(route('api.v1.me.journey'))
        ->assertOk()
        ->assertJsonPath('data.quarter', [
            'completed' => 2,
            'goal' => 4,
            'starts_at' => '2026-07-01',
            'ends_at' => '2026-09-30',
        ]);
});

it('takes the quarter goal from the config', function (): void {
    config()->set('api.quarter_goal', 6);
    actingAsApiEmployee();

    getJson(route('api.v1.me.journey'))->assertJsonPath('data.quarter.goal', 6);
});

it('returns six months of health history ending in the current month', function (): void {
    $this->travelTo('2026-09-15 12:00:00');
    actingAsApiEmployee();

    $history = getJson(route('api.v1.me.journey'))->json('data.health_history');

    expect(array_column($history, 'month'))->toBe(['2026-04', '2026-05', '2026-06', '2026-07', '2026-08', '2026-09']);
});

it('flags the life moment by how much it weighs', function (?LifeMoment $stage, string $statusLabel, string $tone): void {
    $employee = actingAsApiEmployee();

    if ($stage instanceof LifeMoment) {
        UserAnamnese::factory()->for($employee)->create(['life_moment' => $stage]);
        Sanctum::actingAs($employee->fresh(), ['employee']);
    }

    getJson(route('api.v1.me.journey'))
        ->assertJsonPath('data.focus.0', [
            'label' => 'Momento financeiro',
            'status_label' => $statusLabel,
            'tone' => $tone,
        ]);
})->with([
    'no anamnese' => [null, 'a definir', 'info'],
    'endebted' => [LifeMoment::Endebted, 'Endividado', 'warning'],
    'messy' => [LifeMoment::Messy, 'Bagunçado', 'warning'],
    'payer' => [LifeMoment::Payer, 'Pagador', 'info'],
    'saver' => [LifeMoment::Saver, 'Poupador', 'success'],
    'investor' => [LifeMoment::Investor, 'Investidor', 'success'],
]);

it('flags how many topics the consultancy covered', function (int $topics, string $tone): void {
    $employee = actingAsApiEmployee();

    foreach (array_slice(AppointmentCategoryEnum::cases(), 0, $topics) as $category) {
        completedAppointmentFor($employee, ['category_type' => $category]);
    }

    getJson(route('api.v1.me.journey'))
        ->assertJsonPath('data.focus.1', [
            'label' => 'Assuntos da consultoria',
            'status_label' => $topics . ' de 6 assuntos',
            'tone' => $tone,
        ]);
})->with([
    'none' => [0, 'warning'],
    'a third' => [2, 'warning'],
    'half' => [3, 'info'],
    'two thirds' => [4, 'success'],
    'all' => [6, 'success'],
]);

it('flags the meetings still waiting for a rating', function (): void {
    $employee = actingAsApiEmployee();

    getJson(route('api.v1.me.journey'))
        ->assertJsonPath('data.focus.2', ['label' => 'Avaliações', 'status_label' => 'em dia', 'tone' => 'success']);

    completedAppointmentFor($employee);
    app()->forgetScopedInstances();

    getJson(route('api.v1.me.journey'))
        ->assertJsonPath('data.focus.2.status_label', '1 encontro sem avaliação')
        ->assertJsonPath('data.focus.2.tone', 'warning');

    completedAppointmentFor($employee);
    $rated = completedAppointmentFor($employee);
    AppointmentFeedback::factory()->create(['user_id' => $employee->id, 'appointment_id' => $rated->id]);
    app()->forgetScopedInstances();

    getJson(route('api.v1.me.journey'))
        ->assertJsonPath('data.focus.2.status_label', '2 encontros sem avaliação');
});
