<?php

use App\Filament\Admin\Pages\Dashboard;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(Dashboard::class)
        ->assertOk();
});
