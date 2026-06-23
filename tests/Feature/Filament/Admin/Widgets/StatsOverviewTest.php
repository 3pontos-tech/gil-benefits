<?php

use TresPontosTech\PanelAdmin\Filament\Widgets\StatsOverview;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(StatsOverview::class)
        ->assertOk();
});
