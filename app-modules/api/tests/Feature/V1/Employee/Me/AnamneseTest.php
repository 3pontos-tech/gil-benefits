<?php

declare(strict_types=1);

use Laravel\Sanctum\Sanctum;
use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

beforeEach(function (): void {
    $this->travelTo('2026-10-08 10:00:00');
    $this->employee = actingAsApiEmployee();
});

it('answers every field as null before anything is answered', function (): void {
    getJson(route('api.v1.me.anamnese.show'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'life_moment' => null,
            'main_motivation' => null,
            'money_relationship' => null,
            'plans_monthly_expenses' => null,
            'tried_financial_strategies' => null,
            'updated_at' => null,
        ]]);
});

it('saves one answer at a time and keeps the others', function (): void {
    putJson(route('api.v1.me.anamnese.update'), ['main_motivation' => 'Sair do cartão'])
        ->assertOk()
        ->assertJsonPath('data.main_motivation', 'Sair do cartão')
        ->assertJsonPath('data.life_moment', null)
        ->assertJsonPath('data.updated_at', '2026-10-08T10:00:00-03:00');

    putJson(route('api.v1.me.anamnese.update'), ['life_moment' => 'messy'])
        ->assertOk()
        ->assertJsonPath('data.life_moment', 'messy')
        ->assertJsonPath('data.main_motivation', 'Sair do cartão');

    putJson(route('api.v1.me.anamnese.update'), ['main_motivation' => null])
        ->assertJsonPath('data.main_motivation', null)
        ->assertJsonPath('data.life_moment', 'messy');

    expect(UserAnamnese::query()->where('user_id', $this->employee->id)->count())->toBe(1);
});

it('reports the anamnese as completed only with the five answers', function (): void {
    putJson(route('api.v1.me.anamnese.update'), [
        'life_moment' => 'saver',
        'main_motivation' => 'a',
        'money_relationship' => 'b',
        'plans_monthly_expenses' => 'c',
    ])->assertOk();
    Sanctum::actingAs($this->employee->fresh(), ['employee']);

    getJson(route('api.v1.me.show'))
        ->assertJsonPath('data.anamnese_completed', false)
        ->assertJsonPath('data.life_moment', 'saver');

    putJson(route('api.v1.me.anamnese.update'), ['tried_financial_strategies' => 'd'])->assertOk();
    Sanctum::actingAs($this->employee->fresh(), ['employee']);

    getJson(route('api.v1.me.show'))->assertJsonPath('data.anamnese_completed', true);
});

it('uses the life moment in the journey as soon as it is answered', function (): void {
    putJson(route('api.v1.me.anamnese.update'), ['life_moment' => 'endebted'])->assertOk();
    Sanctum::actingAs($this->employee->fresh(), ['employee']);
    app()->forgetScopedInstances();

    getJson(route('api.v1.me.journey'))
        ->assertJsonPath('data.stage', 'endebted')
        ->assertJsonPath('data.stage_index', 0);
});

it('validates the answers', function (array $payload, string $field): void {
    putJson(route('api.v1.me.anamnese.update'), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);

    expect(UserAnamnese::query()->count())->toBe(0);
})->with([
    'unknown life moment' => [['life_moment' => 'rich'], 'life_moment'],
    'text too long' => [['money_relationship' => str_repeat('a', 5001)], 'money_relationship'],
    'nothing to save' => [[], 'life_moment'],
]);

it('exposes every key the app reads from Anamnese', function (): void {
    UserAnamnese::factory()->for($this->employee)->create();

    $response = getJson(route('api.v1.me.anamnese.show'))
        ->assertJsonStructure(['data' => ['life_moment', 'main_motivation', 'money_relationship', 'plans_monthly_expenses', 'tried_financial_strategies', 'updated_at']]);

    expect($response->json('data.updated_at'))->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});
