<?php

use App\Models\Users\User;
use TresPontosTech\Company\Actions\AttachToDefaultCompany;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

it('creates the default company and attaches the user as an employee in the pivot', function (): void {
    $user = User::factory()->create();

    resolve(AttachToDefaultCompany::class)->execute($user, Roles::Employee);

    $company = Company::query()->where('slug', 'flamma-company')->first();

    expect($company)->not->toBeNull();
    expect(
        $company->employees()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', Roles::Employee->value)
            ->exists()
    )->toBeTrue();

    // Company role lives in the pivot; the global role is the baseline "user".
    expect($user->fresh()->hasRole(Roles::User))->toBeTrue();
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

it('attaches consultants to the shared company as employee while keeping the consultant global role', function (): void {
    $user = User::factory()->create();

    resolve(AttachToDefaultCompany::class)->execute($user, Roles::Consultant);

    $company = Company::query()->where('slug', 'flamma-company')->first();

    expect($user->fresh()->hasRole(Roles::Consultant))->toBeTrue();
    expect(
        $company->employees()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', Roles::Employee->value)
            ->exists()
    )->toBeTrue();
});
