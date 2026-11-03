<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\PanelAdmin\Filament\Resources\Plans\Pages\EditPlan;
use TresPontosTech\PanelAdmin\Filament\Resources\Plans\Pages\ListPlans;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
});

it('offers no permanent delete on a trashed plan', function (): void {
    $plan = Plan::factory()->create();
    $plan->delete();

    livewire(EditPlan::class, ['record' => $plan->getRouteKey()])
        ->assertActionDoesNotExist('forceDelete')
        ->assertActionExists('restore');

    livewire(ListPlans::class)
        ->filterTable('trashed', true)
        ->assertTableActionDoesNotExist('forceDelete')
        ->assertTableBulkActionDoesNotExist('forceDelete')
        ->assertTableActionExists('restore')
        ->assertTableBulkActionExists('restore');
});
