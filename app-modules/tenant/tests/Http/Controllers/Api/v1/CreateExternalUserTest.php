<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use TresPontosTech\Company\Models\Company;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

it('should be able to create an external user', function () {
    $company = Company::factory()->create();

    $response = postJson(route('api.v1.company.users.store', ['tenant' => $company->getKey()]), [
        'name' => 'Fulaninho',
        'email' => 'fulaninho@gmail.com',
        'external_id' => '123456',
    ]);
    dd($response->json());

    assertDatabaseHas('users', [
        'name' => 'Fulaninho',
        'email' => 'fulaninho@gmail.com',
        'external_id' => '123456',
    ]);

});
