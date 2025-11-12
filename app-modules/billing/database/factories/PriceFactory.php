<?php

namespace TresPontosTech\Billing\Database\Factories;

use Illuminate\Support\Facades\Date;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Billing\Core\Models\Plan;
use TresPontosTech\Billing\Core\Models\Price;

/** @extends Factory<Price> */
class PriceFactory extends Factory
{
    protected $model = Price::class;

    public function definition(): array
    {
        return [
            'default' => $this->faker->boolean(),
            'billing_scheme' => $this->faker->word(),
            'tiers_mode' => $this->faker->word(),
            'type' => $this->faker->word(),
            'unit_amount_decimal' => $this->faker->randomNumber(),
            'active' => $this->faker->boolean(),
            'provider_price_id' => $this->faker->word(),
            'metadata' => $this->faker->words(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
            'billing_plan_id' => Plan::factory(),
        ];
    }
}
