<?php

use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    $this->company = Company::factory()->create();
});

it('returns 401 when the required header is missing', function (): void {
    $response = postJson(
        route('api.v1.company.users.store', ['tenant' => $this->company->slug]),
        ['name' => 'Test', 'email' => 'test@example.com', 'external_id' => '123'],
        [] // No header
    );

    expect($response->status())->toBe(Response::HTTP_UNAUTHORIZED);
    expect($response->json('error'))->toBe('Unauthorized');
});

it('returns 403 when the token does not belong to any company', function (): void {
    $response = postJson(
        route('api.v1.company.users.store', ['tenant' => $this->company->slug]),
        ['name' => 'Test', 'email' => 'test@example.com', 'external_id' => '123'],
        [config('tenant.header') => 'invalid-token-that-does-not-exist']
    );

    expect($response->status())->toBe(Response::HTTP_FORBIDDEN);
});

it('returns 403 when using a token from a different tenant', function (): void {
    $otherCompany = Company::factory()->create();

    $response = postJson(
        route('api.v1.company.users.store', ['tenant' => $otherCompany->slug]),
        ['name' => 'Test', 'email' => 'test@example.com', 'external_id' => '123'],
        [config('tenant.header') => $this->company->integration_access_key]
    );

    expect($response->status())->toBe(Response::HTTP_FORBIDDEN);
});

it('allows the request when the token and tenant are both valid', function (): void {
    $response = postJson(
        route('api.v1.company.users.store', ['tenant' => $this->company->slug]),
        ['name' => 'Test User', 'email' => 'test@example.com', 'external_id' => '123'],
        [config('tenant.header') => $this->company->integration_access_key]
    );

    expect($response->status())->toBe(Response::HTTP_CREATED);
});
