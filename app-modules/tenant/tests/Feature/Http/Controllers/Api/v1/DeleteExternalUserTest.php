<?php

use App\Models\Users\User;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\deleteJson;
use function PHPUnit\Framework\assertTrue;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->company->employees()->save($this->user);
});

it('should be able to delete an user and detach for company', function (): void {
    $response = deleteJson(route('api.v1.company.users.delete', ['tenant' => $this->company->getKey(), 'user' => $this->user->getKey()]), [], [
        config('tenant.header') => $this->company->integration_access_key,
    ]);

    $response->assertStatus(204);

    $user = $this->user->refresh();

    assertTrue($user->trashed());

    $userCompany = $user->companies();

    expect($userCompany->count())->toBe(0);
});

test('should return unauthorized status when header is wrong', function (): void {
    $response = deleteJson(route('api.v1.company.users.delete', ['tenant' => $this->company->getKey(), 'user' => $this->user->getKey()]), [], [
        'Fuedase' => 123,
    ]);

    $response->assertStatus(401);
});

it('should fail when company does not exists', function (): void {
    $response = deleteJson(route('api.v1.company.users.delete', ['tenant' => 123, 'user' => $this->user->getKey()]), [], [
        config('tenant.header') => 123,
    ]);

    $response->assertStatus(Response::HTTP_FORBIDDEN);
});

it('should not allow deleting a user from another tenant using a valid token from a different company', function (): void {
    $otherCompany = Company::factory()->create();
    $otherCompany->employees()->save($this->user);

    $response = deleteJson(route('api.v1.company.users.delete', ['tenant' => $otherCompany->getKey(), 'user' => $this->user->getKey()]), [], [
        config('tenant.header') => $this->company->integration_access_key,
    ]);

    $response->assertStatus(Response::HTTP_FORBIDDEN);

    expect($otherCompany->employees()->count())->toBe(1);
});
