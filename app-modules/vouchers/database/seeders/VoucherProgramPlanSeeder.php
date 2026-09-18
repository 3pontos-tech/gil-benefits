<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Database\Seeders;

use Illuminate\Database\Seeder;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;

class VoucherProgramPlanSeeder extends Seeder
{
    public const SLUG = 'programa-vouchers';

    public function run(): void
    {
        Plan::query()->updateOrCreate(
            [
                'provider' => BillingProviderEnum::Contractual,
                'slug' => self::SLUG,
            ],
            [
                'name' => 'Programa de Vouchers',
                'description' => 'Parceria em que a empresa distribui consultoria por voucher, sem cota mensal.',
                'provider_product_id' => null,
                'active' => true,
                'type' => BillableTypeEnum::Company,
                'trial_days' => null,
                'has_generic_trial' => false,
                'allow_promotion_codes' => false,
                'collect_tax_ids' => false,
                'unit_label' => 'empresa',
                'statement_descriptor' => null,
            ]
        );
    }
}
