<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Discounts\Models\Discount;

/** @extends Factory<Discount> */
class DiscountFactory extends Factory
{
    protected $model = Discount::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->sentence(3),
            'percent_off' => 30,
            'audience' => PriceAudienceEnum::Standalone,
            'claim_window_days' => 180,
            'active' => true,
        ];
    }

    public function active(): static
    {
        return $this->state(['active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
