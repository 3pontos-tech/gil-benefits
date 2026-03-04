<?php

use App\Filament\Admin\Widgets\QuickActions;

use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(QuickActions::class)
        ->assertOk();
});
