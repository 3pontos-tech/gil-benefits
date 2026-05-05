<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Database\Seeders;

use Illuminate\Database\Seeder;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;

class BartePlanSeeder extends Seeder
{
    public function run(): void
    {
        $barteUuid = env('BARTE_SANDBOX_PLAN_UUID', 'uuid-do-plano-no-sandbox');

        $plan = Plan::query()->updateOrCreate(['slug' => 'barte-essencial'], [
            'name' => 'Essencial',
            'description' => 'Plano essencial de benefícios',
            'provider' => BillingProviderEnum::Barte,
            'provider_product_id' => $barteUuid,
            'active' => true,
            'type' => BillableTypeEnum::Company,
            'trial_days' => null,
            'has_generic_trial' => false,
            'allow_promotion_codes' => false,
            'collect_tax_ids' => false,
            'unit_label' => 'empresa',
            'statement_descriptor' => 'GIL BENEFICIOS',
        ]);

        Price::query()->updateOrCreate(['provider_price_id' => $barteUuid], [
            'billing_plan_id' => $plan->id,
            'unit_amount_decimal' => 4990,
            'type' => 'recurring',
            'billing_scheme' => 'per_unit',
            'tiers_mode' => 'volume',
            'active' => true,
            'default' => true,
            'monthly_appointments' => 4,
            'whatsapp_enabled' => false,
            'materials_enabled' => false,
        ]);
    }
}
