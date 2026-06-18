<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;

it('prefers the company plan quota over an individual subscription', function (): void {
    $employee = actingAsEmployee(); // CompanyPlan ativo: 1 agendamento/mês

    // Assinatura individual com cota MAIOR (5) — não deve prevalecer.
    $plan = Plan::factory()->createOne([
        'type' => BillableTypeEnum::User->value,
        'active' => true,
    ]);

    $price = Price::query()->create([
        'billing_plan_id' => $plan->id,
        'billing_scheme' => 'per_unit',
        'tiers_mode' => 'volume',
        'type' => 'recurring',
        'unit_amount_decimal' => 5000,
        'active' => true,
        'provider_price_id' => 'price_individual_test',
        'monthly_appointments' => 5,
        'whatsapp_enabled' => true,
        'materials_enabled' => true,
    ]);

    $employee->subscriptions()->create([
        'type' => 'default',
        'stripe_id' => 'sub_individual_123',
        'stripe_status' => 'active',
        'stripe_price' => $price->provider_price_id,
        'quantity' => 1,
    ]);

    expect($employee->fresh()->monthly_appointments_left)->toBe(1);
});
