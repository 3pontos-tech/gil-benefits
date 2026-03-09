<?php

use TresPontosTech\Admin\Filament\Widgets\StatsOverview;

use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(StatsOverview::class)
        ->assertOk();
});
