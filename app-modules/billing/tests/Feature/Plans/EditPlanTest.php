<?php

use TresPontosTech\Admin\Filament\Resources\Plans\Pages\EditPlan;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsSuperAdmin();
});

it('should render', function (): void {
    $plan = Plan::factory()->create(['provider' => BillingProviderEnum::Stripe]);

    livewire(EditPlan::class, ['record' => $plan->getKey()])
        ->assertOk();
});
