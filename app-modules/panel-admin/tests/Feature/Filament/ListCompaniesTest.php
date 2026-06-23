<?php

declare(strict_types=1);

use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelAdmin\Filament\Resources\Companies\Pages\ListCompanies;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(ListCompanies::class)
        ->assertOk();
});

it('should list all companies', function (): void {
    $companies = Company::factory()->count(10)->create();
    livewire(ListCompanies::class)
        ->assertOk()
        ->assertCanSeeTableRecords($companies);

});
