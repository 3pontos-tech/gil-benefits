<?php

use TresPontosTech\PanelAdmin\Filament\Widgets\LatestCompanies;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(LatestCompanies::class)
        ->assertOk();
});
