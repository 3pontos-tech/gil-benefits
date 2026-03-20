<?php

use TresPontosTech\Admin\Filament\Resources\Prices\Pages\CreatePrice;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    actingAsAdmin();
});

it('should render', function (): void {
    livewire(CreatePrice::class)
        ->assertOk();
});

it('should be able to register a price', function (): void {
    $plan = Plan::factory()->create();
    livewire(CreatePrice::class)
        ->assertOk()
        ->fillForm([
            'billing_plan_id' => $plan->getKey(),
            'type' => 'one_time',
            'billing_scheme' => 'per_unit',
            'tiers_mode' => 'graduated',
            'unit_amount_decimal' => 2,
            'monthly_appointments' => 1,
            'active' => true,
            'whatsapp_enabled' => false,
            'materials_enabled' => false,
            'metadata' => '{"key":"value"}',
        ])
        ->set('data.provider_price_id', '123786')
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseCount(Price::class, 1);
    assertDatabaseHas(Price::class, [
        'billing_plan_id' => $plan->getKey(),
        'type' => 'one_time',
        'billing_scheme' => 'per_unit',
        'tiers_mode' => 'graduated',
        'unit_amount_decimal' => 2,
        'monthly_appointments' => 1,
        'active' => true,
        'whatsapp_enabled' => false,
        'materials_enabled' => false,
        'metadata' => '{"key":"value"}',
    ]);
});
