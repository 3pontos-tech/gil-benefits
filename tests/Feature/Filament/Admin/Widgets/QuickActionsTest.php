<?php

use TresPontosTech\Admin\Filament\Widgets\QuickActions;

use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(QuickActions::class)
        ->assertOk();
});
