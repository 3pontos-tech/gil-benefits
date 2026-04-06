<?php

use App\Models\Users\User;
use TresPontosTech\App\Filament\Pages\UserRegistration;
use TresPontosTech\Company\Models\Company;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('should render', function () {
    livewire(UserRegistration::class)
        ->assertOk();
});

it('should register user to flamma company', function () {
    livewire(UserRegistration::class)
        ->assertOk()
        ->fillForm([
            'name' => 'John',
            'email' => 'joe@doe.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    assertDatabaseCount(User::class, 1);
    assertDatabaseHas(User::class, [
        'name' => 'John',
        'email' => 'joe@doe.com',
    ]);

    $user = User::first();
    $flammaCompany = Company::query()->where('slug', 'flamma-company')->first();

    assertAuthenticatedAs($user);
    expect($user->companies()->first()->slug)->toBe($flammaCompany->slug)
        ->and($user->isEmployee())->toBeTrue();
});
