<?php

use App\Models\Users\Detail;
use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Filament\Pages\UserRegistration;

use function Pest\Laravel\assertAuthenticatedAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
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

it('should not register a user with a tax_id that already exists', function (): void {
    Detail::factory()->create(['tax_id' => '56259004770']);

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
        ->assertHasFormErrors(['tax_id']);

    assertDatabaseMissing(User::class, ['email' => 'joe@doe.com']);
    assertDatabaseCount(Detail::class, 1);
});

it('should not register a user with a document_id that already exists', function (): void {
    Detail::factory()->create(['document_id' => '1234567890']);

    livewire(UserRegistration::class)
        ->assertOk()
        ->fillForm([
            'name' => 'John',
            'email' => 'joe@doe.com',
            'password' => 'password123',
            'passwordConfirmation' => 'password123',
            'tax_id' => '562.590.047-70',
            'document_id' => '123.456.789-0',
        ])
        ->call('register')
        ->assertHasFormErrors(['document_id']);

    assertDatabaseMissing(User::class, ['email' => 'joe@doe.com']);
    assertDatabaseCount(Detail::class, 1);
});
