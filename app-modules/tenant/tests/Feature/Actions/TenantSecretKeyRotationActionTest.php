<?php

use Ramsey\Uuid\Uuid;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Tenant\Actions\TenantSecretKeyRotationAction;

it('generates a new UUID and updates the company integration access key', function (): void {
    $company = Company::factory()->create();
    $oldKey = $company->integration_access_key;

    $newKey = resolve(TenantSecretKeyRotationAction::class)->generate($company);

    $company->refresh();

    expect($newKey)->not->toBe($oldKey);
    expect($company->integration_access_key)->toBe($newKey);
    expect(Uuid::isValid($newKey))->toBeTrue();
});

it('the generated key is a valid UUID v4', function (): void {
    $company = Company::factory()->create();

    $newKey = resolve(TenantSecretKeyRotationAction::class)->generate($company);

    expect(Uuid::fromString($newKey)->getVersion())->toBe(4);
});

it('replaces the previous token completely', function (): void {
    $company = Company::factory()->create();
    $originalKey = $company->integration_access_key;

    $firstRotation = resolve(TenantSecretKeyRotationAction::class)->generate($company);
    $company->refresh();
    expect($company->integration_access_key)->toBe($firstRotation);

    $secondRotation = resolve(TenantSecretKeyRotationAction::class)->generate($company);
    $company->refresh();

    expect($company->integration_access_key)->toBe($secondRotation);
    expect($company->integration_access_key)->not->toBe($originalKey);
    expect($company->integration_access_key)->not->toBe($firstRotation);
});
