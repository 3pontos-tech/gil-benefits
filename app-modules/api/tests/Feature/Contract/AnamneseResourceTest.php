<?php

declare(strict_types=1);

use TresPontosTech\User\Models\UserAnamnese;

use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;

it('exposes every key the app reads from Anamnese', function (): void {
    $employee = actingAsApiEmployee();
    UserAnamnese::factory()->for($employee)->create();

    $response = getJson(route('api.v1.me.anamnese.show'))
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'life_moment', 'main_motivation', 'money_relationship',
            'plans_monthly_expenses', 'tried_financial_strategies', 'updated_at',
        ]]);

    expect($response->json('data.updated_at'))->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/');
});

it('answers the update with the same shape and status 200', function (): void {
    actingAsApiEmployee();

    putJson(route('api.v1.me.anamnese.update'), ['life_moment' => 'messy'])
        ->assertOk()
        ->assertJsonStructure(['data' => [
            'life_moment', 'main_motivation', 'money_relationship',
            'plans_monthly_expenses', 'tried_financial_strategies', 'updated_at',
        ]]);
});
