<?php

declare(strict_types=1);

use TresPontosTech\PanelCompany\Filament\Pages\CommandDashboard;

use function Pest\Livewire\livewire;

it('renders the command dashboard page for a company owner', function (): void {
    actingAsCompanyOwner();

    livewire(CommandDashboard::class)
        ->assertOk()
        ->assertSee(__('panel-company::resources.pages.command_dashboard.view_metrics'));
});
