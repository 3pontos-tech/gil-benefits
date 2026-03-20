<?php

use TresPontosTech\Admin\Filament\Resources\Plans\Pages\CreatePlan;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
});

it('should render', function (): void {
    livewire(CreatePlan::class)
        ->assertOk();
});
