<?php

use App\Models\Users\User;
use Illuminate\Support\Str;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\CreateCompany;
use TresPontosTech\Permissions\Roles;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;
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
            'tax_id' => '57.181.164/0001-80',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Company::class, [
        'user_id' => auth()->user()->getKey(),
        'name' => 'my company',
        'tax_id' => '57181164000180',
    ]);
});
it('company slug should be unique', function (): void {
    Company::factory()->create([
        'user_id' => auth()->user()->getKey(),
        'name' => 'my company',
        'tax_id' => '57.181.164/0001-80',
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
        ->assertHasFormErrors(['slug' => 'unique']);
});

test('after creating should assign company owner role to the owner', function (): void {
    $companyOwner = User::factory()->createQuietly();

    livewire(CreateCompany::class)
        ->assertOk()
        ->fillForm([
            'user_id' => $companyOwner->getKey(),
            'name' => 'my company',
            'tax_id' => '57.181.164/0001-80',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Company::class, [
        'user_id' => $companyOwner->getKey(),
        'name' => 'my company',
        'tax_id' => '57181164000180',
    ]);

    // The company owner role lives in the company_employees pivot, not as a global role.
    $company = Company::query()->where('tax_id', '57181164000180')->first();
    assertTrue(
        $company->employees()
            ->wherePivot('user_id', $companyOwner->getKey())
            ->wherePivot('role', Roles::CompanyOwner->value)
            ->exists()
    );
});
test('should attach owner after creating', function (): void {
    livewire(CreateCompany::class)
        ->assertOk()
        ->fillForm([
            'user_id' => auth()->user()->getKey(),
            'name' => 'my company',
            'tax_id' => '57.181.164/0001-80',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas(Company::class, [
        'user_id' => auth()->user()->getKey(),
        'name' => 'my company',
        'tax_id' => '57181164000180',
    ]);

    $company = Company::query()->where('tax_id', '57181164000180')->first();
    assertTrue(
        $company->employees()
            ->wherePivot('user_id', auth()->user()->getKey())
            ->wherePivot('role', Roles::CompanyOwner->value)
            ->exists()
    );
});
