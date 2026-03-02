<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\Tenant\Filament\Pages\Tenancy\RegisterTenant;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAs(User::factory()->create());
    filament()->setCurrentPanel(FilamentPanel::Company->value);
});

it('should render', function (): void {
    livewire(RegisterTenant::class)
        ->assertOk();
});

it('should be able to register a tenant (company)', function (): void {
    livewire(RegisterTenant::class)
        ->assertOk()
        ->fillForm([
            'name' => 'fuedase',
            'tax_id' => '76520789000173',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    assertDatabaseCount('companies', 1);
    assertdatabaseHas('companies', [
        'name' => 'fuedase',
        'tax_id' => '76520789000173',
    ]);

    $company = Company::query()->where('companies.tax_id', '76520789000173')->first();

    expect($company->integration_access_key)->not()->toBeNull();
});

test('user should be the owner after registering the company', function (): void {
    livewire(RegisterTenant::class)
        ->assertOk()
        ->fillForm([
            'name' => 'fuedase',
            'tax_id' => '76520789000173',
        ])
        ->call('register')
        ->assertHasNoFormErrors();

    assertDatabaseCount('companies', 1);
    assertdatabaseHas('companies', [
        'name' => 'fuedase',
        'tax_id' => '76520789000173',
    ]);

    $company = Company::query()->where('companies.tax_id', '76520789000173')->first();

    expect($company->integration_access_key)->not()->toBeNull();
    expect($company->owner()->first()->getKey())->toBe(auth()->user()->getKey())
        ->and(auth()->user()->hasRole([Roles::CompanyOwner]))
        ->and($company->employees()->where('user_id', auth()->user()->getKey())->exists());
});
