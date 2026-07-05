<?php

use App\Models\Users\User;
use TresPontosTech\Company\Listeners\AttachUserToDefaultCompanyListener;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Events\UserRegistered;

it('attaches the user to the default company as an employee when the event is handled', function (): void {
    $user = User::factory()->create();
    $event = new UserRegistered($user, Roles::Employee);

    resolve(AttachUserToDefaultCompanyListener::class)->handle($event);

    $company = Company::query()->where('slug', 'flamma-company')->first();

    expect($company)->not->toBeNull();
    expect(
        $company->employees()
            ->wherePivot('user_id', $user->id)
            ->wherePivot('role', Roles::Employee->value)
            ->exists()
    )->toBeTrue();

    expect($user->fresh()->hasRole(Roles::User))->toBeTrue();
});

it('does not attach a consultant to the default company', function (): void {
    $user = User::factory()->create();
    $event = new UserRegistered($user, Roles::Consultant);

    resolve(AttachUserToDefaultCompanyListener::class)->handle($event);

    expect($user->fresh()->hasRole(Roles::Consultant))->toBeTrue()
        ->and($user->companies()->count())->toBe(0);
});
