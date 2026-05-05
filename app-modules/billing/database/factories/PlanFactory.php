<?php

namespace TresPontosTech\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Billing\Core\Enums\BillableTypeEnum;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\Plan;

/** @extends Factory<Plan> */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'provider' => $this->faker->randomElement(BillingProviderEnum::cases()),
            'provider_product_id' => $this->faker->word(),
            'trial_days' => $this->faker->randomNumber(),
            'has_generic_trial' => $this->faker->boolean(),
            'allow_promotion_codes' => $this->faker->boolean(),
            'collect_tax_ids' => $this->faker->boolean(),
            'active' => $this->faker->boolean(),
            'slug' => $this->faker->slug(),
            'type' => $this->faker->randomElement(BillableTypeEnum::cases()),
            'unit_label' => $this->faker->word(),
            'statement_descriptor' => $this->faker->word(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }

    public function active(): self
    {
        return $this->state(function (array $attributes): array {
            return [
                'active' => true,
            ];
        });
    }

    public function stripe(): self
    {
        return $this->state([
            'provider' => BillingProviderEnum::Stripe,
            'trial_days' => null,
            'has_generic_trial' => false,
            'allow_promotion_codes' => false,
            'collect_tax_ids' => false,
        ]);
    }

    public function barte(): self
    {
        return $this->state([
            'provider' => BillingProviderEnum::Barte,
            'trial_days' => null,
            'has_generic_trial' => false,
            'allow_promotion_codes' => false,
            'collect_tax_ids' => false,
        ]);
    }

    public function contractual(): self
    {
        return $this->state([
            'provider' => BillingProviderEnum::Contractual,
            'provider_product_id' => null,
            'statement_descriptor' => null,
            'trial_days' => null,
            'has_generic_trial' => false,
            'allow_promotion_codes' => false,
            'collect_tax_ids' => false,
        ]);
    }
}
