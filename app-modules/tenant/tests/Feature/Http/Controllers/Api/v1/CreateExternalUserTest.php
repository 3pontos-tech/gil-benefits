<?php

use App\Models\Users\User;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
});

it('should be able to create an external user', function (): void {
    $response = postJson(route('api.v1.company.users.store', ['tenant' => $this->company->getKey()]), [
        'name' => 'Fulaninho',
        'email' => 'fulaninho@gmail.com',
        'external_id' => '123456',
    ], [
        config('tenant.header') => $this->company->integration_access_key,
    ]);

    $response->assertStatus(201);

    assertDatabaseCount('users', 2);
    assertDatabaseHas('users', [
        'name' => 'Fulaninho',
        'email' => 'fulaninho@gmail.com',
        'external_id' => '123456',
    ]);

    $user = User::query()->where('users.email', 'fulaninho@gmail.com')->first();
    $userCompany = $user->companies();

    expect($userCompany->count())->toBe(1)
        ->and($userCompany->first()->getKey())->toBe($this->company->getKey());
});

test('should return unauthorized status when header is wrong', function (): void {
    $response = postJson(route('api.v1.company.users.store', ['tenant' => $this->company->getKey()]), [
        'name' => 'Fulaninho',
        'email' => 'fulaninho@gmail.com',
        'external_id' => '123456',
    ], [
        'Fuedase' => 123,
    ]);

    $response->assertStatus(401);
});

it('should fail when company does not exists', function (): void {
    $response = postJson(route('api.v1.company.users.store', ['tenant' => 123]), [
        'name' => 'Fulaninho',
        'email' => 'fulaninho@gmail.com',
        'external_id' => '123456',
    ], [
        config('tenant.header') => 123,
    ]);

    $response->assertStatus(Response::HTTP_FORBIDDEN);
});
