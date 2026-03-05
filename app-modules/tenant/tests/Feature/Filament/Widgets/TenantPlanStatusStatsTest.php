<?php

use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Widgets\TenantPlanStatusStats;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    $tenant = Company::factory()->create();
    filament()->setTenant($tenant);
});

it('should render', function (): void {
    livewire(TenantPlanStatusStats::class)
        ->assertOk();
});
