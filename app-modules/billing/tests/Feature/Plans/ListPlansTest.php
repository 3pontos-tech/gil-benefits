<?php

use TresPontosTech\Billing\Core\Filament\Admin\Resources\Plans\Pages\ListPlans;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
});

it('should render', function (): void {
    livewire(ListPlans::class)
        ->assertOk();
});
