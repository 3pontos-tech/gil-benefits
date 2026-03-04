<?php

use TresPontosTech\Billing\Core\Filament\Admin\Resources\Plans\Pages\EditPlan;
use TresPontosTech\Billing\Core\Models\Plan;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
});

it('should render', function (): void {
    $plan = Plan::factory()->create();

    livewire(EditPlan::class, ['record' => $plan->getKey()])
        ->assertOk();
});
