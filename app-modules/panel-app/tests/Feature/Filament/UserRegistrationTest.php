<?php

use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Filament\Pages\UserRegistration;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

it('should render', function (): void {
    livewire(UserRegistration::class)
        ->assertOk();
});

it('should register user to flamma company', function (): void {
    livewire(UserRegistration::class)
        ->assertOk()
        ->fillForm([
            'name' => 'John',
            'email' => 'joe@doe.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
            'tax_id' => '562.590.047-70',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    assertDatabaseCount(User::class, 1);
    assertDatabaseHas(User::class, [
        'name' => 'John',
        'email' => 'joe@doe.com',
    ]);

    $user = User::query()->first();
    $flammaCompany = Company::query()->where('slug', 'flamma-company')->first();

    assertAuthenticatedAs($user);
    expect($user->companies()->first()->slug)->toBe($flammaCompany->slug)
        ->and($user->isEmployee())->toBeTrue();
});
