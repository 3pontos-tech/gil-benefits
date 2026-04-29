<?php

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\PanelCompany\Filament\Pages\Tenancy\EditTenantProfile;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsCompanyOwner();
    $this->company = filament()->getTenant();
});

it('shows seat count using active stripe subscription quantity when no contractual plan exists', function (): void {
    livewire(EditTenantProfile::class)
        ->assertOk()
        ->assertSee('Assentos: 1/10');
});

it('shows seat count using contractual plan seats when a contractual plan is active', function (): void {
    CompanyPlan::factory()->active()->for($this->company, 'company')->create(['seats' => 5]);

    livewire(EditTenantProfile::class)
        ->assertOk()
        ->assertSee('Assentos: 1/5');
});

it('counts only active employees in the seat display', function (): void {
    $inactiveEmployee = User::factory()->employee()->create();
    $this->company->employees()->attach($inactiveEmployee->getKey(), ['active' => false]);

    livewire(EditTenantProfile::class)
        ->assertOk()
        ->assertSee('Assentos: 1/10');
});
