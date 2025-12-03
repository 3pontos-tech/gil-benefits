<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Tenant\Filament\Pages\Tenancy\EditTenantProfile;
use TresPontosTech\Tenant\Filament\Pages\Tenancy\RegisterTenant;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $company = Company::factory()->create();
    actingAs(User::factory()->create());
    filament()->setCurrentPanel(FilamentPanel::Company->value);
    filament()->setTenant($company);
});

it('should render', function (): void {
    livewire(EditTenantProfile::class)
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

    assertDatabaseCount('companies', 2);
    assertdatabaseHas('companies', [
        'name' => 'fuedase',
        'tax_id' => '76520789000173',
    ]);

    $company = Company::query()->where('companies.tax_id', '76520789000173')->first();

    expect($company->integration_access_key)->not()->toBeNull();
});
