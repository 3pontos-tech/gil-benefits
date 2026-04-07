<?php

use App\Models\Users\User;
use Illuminate\Support\Str;
use TresPontosTech\Admin\Filament\Resources\Companies\Pages\CreateCompany;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertTrue;

beforeEach(function (): void {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(CreateCompany::class)
        ->assertOk();
});

it('can create a new company', function (): void {
    livewire(CreateCompany::class)
        ->assertOk()
        ->fillForm([
            'user_id' => auth()->user()->getKey(),
            'name' => 'my company',
            'tax_id' => '99.999.999/9999-99',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Company::class, [
        'user_id' => auth()->user()->getKey(),
        'name' => 'my company',
        'tax_id' => '99999999999999',
    ]);
});
it('company slug should be unique', function (): void {
    Company::factory()->create([
        'user_id' => auth()->user()->getKey(),
        'name' => 'my company',
        'tax_id' => '99.999.999/9999-99',
        'slug' => Str::slug('my company'),
    ]);

    livewire(CreateCompany::class)
        ->assertOk()
        ->fillForm([
            'user_id' => auth()->user()->getKey(),
            'name' => 'my company',
            'tax_id' => '12.999.999/9999-99',
            'slug' => Str::slug('my company'),
        ])
        ->call('create')
        ->assertHasFormErrors(['slug' => 'The slug has already been taken.']);
});

test('after creating should assign company owner role to the owner', function (): void {
    $companyOwner = User::factory()->createQuietly();
    assertFalse($companyOwner->hasRole(Roles::CompanyOwner));

    livewire(CreateCompany::class)
        ->assertOk()
        ->fillForm([
            'user_id' => $companyOwner->getKey(),
            'name' => 'my company',
            'tax_id' => '99.999.999/9999-99',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Company::class, [
        'user_id' => $companyOwner->getKey(),
        'name' => 'my company',
        'tax_id' => '99999999999999',
    ]);

    $companyOwner->refresh();
    assertTrue($companyOwner->hasRole(Roles::CompanyOwner));
});
test('should attach owner after creating', function (): void {
    assertFalse(auth()->user()->hasRole(Roles::CompanyOwner));

    livewire(CreateCompany::class)
        ->assertOk()
        ->fillForm([
            'user_id' => auth()->user()->getKey(),
            'name' => 'my company',
            'tax_id' => '99.999.999/9999-99',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Company::class, [
        'user_id' => auth()->user()->getKey(),
        'name' => 'my company',
        'tax_id' => '99999999999999',
    ]);

    auth()->user()->refresh();
    assertTrue(auth()->user()->hasRole(Roles::CompanyOwner));
    $company = Company::query()->where('tax_id', '99999999999999')->first();
    assertTrue($company->employees()->where('user_id', auth()->user()->getKey())->exists());
});
