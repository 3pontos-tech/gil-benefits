<?php

use App\Models\Users\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Company\Actions\CreateCompanyAction;
use TresPontosTech\Company\DTOs\CompanyDTO;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

uses(RefreshDatabase::class);

it('creates a company, attaches the user, and assigns the CompanyOwner role', function (): void {
    $user = User::factory()->create();

    $dto = new CompanyDTO(
        name: 'Test Company',
        slug: 'test-company',
        taxId: '12345678000195',
        integrationAccessKey: (string) Uuid::uuid4(),
        userId: $user->id,
    );

    $company = resolve(CreateCompanyAction::class)->execute($dto);

    expect($company)->toBeInstanceOf(Company::class)
        ->and($company->name)->toBe('Test Company')
        ->and($company->slug)->toBe('test-company');

    expect(
        $company->fresh()->employees()->wherePivot('user_id', $user->id)->exists()
    )->toBeTrue();

    expect($user->fresh()->hasRole(Roles::CompanyOwner))->toBeTrue();
});

it('throws an exception when the user does not exist', function (): void {
    $dto = new CompanyDTO(
        name: 'Test Company',
        slug: 'test-company',
        taxId: '12345678000195',
        integrationAccessKey: (string) Uuid::uuid4(),
        userId: (string) Uuid::uuid4(),
    );

    expect(fn () => resolve(CreateCompanyAction::class)->execute($dto))
        ->toThrow(ModelNotFoundException::class);
});
