<?php

use App\Models\Users\User;
use TresPontosTech\Company\Listeners\AttachUserToDefaultCompanyListener;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;

it('attaches the user to the default company when the event is handled', function (): void {
    $user = User::factory()->create();
    $event = new UserRegistered($user, Roles::Employee);

    resolve(AttachUserToDefaultCompanyListener::class)->handle($event);

    $company = Company::query()->where('slug', 'flamma-company')->first();

    expect($company)->not->toBeNull();
    expect(
        $company->employees()->wherePivot('user_id', $user->id)->exists()
    )->toBeTrue();
    expect($user->fresh()->hasRole(Roles::Employee))->toBeTrue();
});

it('uses the role provided in the event when attaching the user', function (): void {
    $user = User::factory()->create();
    $event = new UserRegistered($user, Roles::CompanyOwner);

    resolve(AttachUserToDefaultCompanyListener::class)->handle($event);

    expect($user->fresh()->hasRole(Roles::CompanyOwner))->toBeTrue();
    expect($user->fresh()->hasRole(Roles::Employee))->toBeFalse();
});
