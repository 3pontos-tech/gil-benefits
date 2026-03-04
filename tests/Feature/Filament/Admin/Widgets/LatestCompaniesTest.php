<?php

use App\Filament\Admin\Widgets\LatestCompanies;

use function Pest\Livewire\livewire;

beforeEach(function () {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(LatestCompanies::class)
        ->assertOk();
});
