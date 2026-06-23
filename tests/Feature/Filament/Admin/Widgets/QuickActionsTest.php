<?php

use TresPontosTech\PanelAdmin\Filament\Widgets\QuickActions;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(QuickActions::class)
        ->assertOk();
});
