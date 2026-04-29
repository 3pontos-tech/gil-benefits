<?php

use App\Models\Users\User;
use TresPontosTech\Company\Actions\AttachToDefaultCompany;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

it('creates the default company, attaches the user, and assigns the given role', function (): void {
    $user = User::factory()->create();

    resolve(AttachToDefaultCompany::class)->execute($user, Roles::Employee);

    $company = Company::query()->where('slug', 'flamma-company')->first();

    expect($company)->not->toBeNull();
    expect(
        $company->employees()->wherePivot('user_id', $user->id)->exists()
    )->toBeTrue();
    expect($user->fresh()->hasRole(Roles::Employee))->toBeTrue();
});

it('does not create a duplicate company when called multiple times (idempotent)', function (): void {
    $firstUser = User::factory()->create();
    $secondUser = User::factory()->create();

    resolve(AttachToDefaultCompany::class)->execute($firstUser, Roles::Employee);
    resolve(AttachToDefaultCompany::class)->execute($secondUser, Roles::Employee);

    expect(Company::query()->where('slug', 'flamma-company')->count())->toBe(1);

    $company = Company::query()->where('slug', 'flamma-company')->first();
    expect($company->employees()->count())->toBe(2);
});

it('assigns the specified role to the user', function (): void {
    $user = User::factory()->create();

    resolve(AttachToDefaultCompany::class)->execute($user, Roles::CompanyOwner);

    expect($user->fresh()->hasRole(Roles::CompanyOwner))->toBeTrue();
    expect($user->fresh()->hasRole(Roles::Employee))->toBeFalse();
});
