<?php

use TresPontosTech\Company\Models\Company;
use TresPontosTech\Tenant\Filament\Widgets\LatestScheduledSessionsTableWidget;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
    $tenant = Company::factory()->create();
    filament()->setTenant($tenant);
});

it('should render', function (): void {
    livewire(LatestScheduledSessionsTableWidget::class)
        ->assertOk();
});
