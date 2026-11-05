<?php

declare(strict_types=1);

use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\ListCompanies;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
});

it('offers no permanent delete on the companies list', function (): void {
    $company = Company::factory()->create();
    $company->delete();

    livewire(ListCompanies::class)
        ->filterTable('trashed', true)
        ->assertTableActionDoesNotExist('forceDelete')
        ->assertTableBulkActionDoesNotExist('forceDelete')
        ->assertTableActionExists('restore')
        ->assertTableBulkActionExists('restore');
});
