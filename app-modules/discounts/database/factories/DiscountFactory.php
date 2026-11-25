<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Billing\Core\Enums\PriceAudienceEnum;
use TresPontosTech\Discounts\Enums\DiscountCustomerTypeEnum;
use TresPontosTech\Discounts\Enums\DiscountDistributionEnum;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Enums\DiscountValueTypeEnum;
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
            'value_type' => DiscountValueTypeEnum::Percent,
            'percent_off' => 30,
            'amount_off_cents' => null,
            'duration' => DiscountDurationEnum::Forever,
            'duration_cycles' => null,
            'distribution' => DiscountDistributionEnum::Grant,
            'code' => null,
            'customer_type' => DiscountCustomerTypeEnum::Person,
            'audience' => PriceAudienceEnum::Standalone,
            'eligible_plan_slugs' => null,
            'starts_at' => null,
            'ends_at' => null,
            'claim_window_days' => 180,
            'max_redemptions' => null,
            'max_redemptions_per_customer' => 1,
            'first_subscription_only' => false,
            'locks_plan' => false,
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

    public function code(?string $code = null): static
    {
        return $this->state([
            'distribution' => DiscountDistributionEnum::Code,
            'code' => $code ?? strtoupper(fake()->unique()->bothify('????##')),
        ]);
    }

    public function automatic(): static
    {
        return $this->state([
            'distribution' => DiscountDistributionEnum::Automatic,
            'code' => null,
        ]);
    }

    public function fixedAmount(int $amountOffCents = 5000): static
    {
        return $this->state([
            'value_type' => DiscountValueTypeEnum::FixedAmount,
            'percent_off' => null,
            'amount_off_cents' => $amountOffCents,
        ]);
    }

    public function locksPlan(): static
    {
        return $this->state(['locks_plan' => true]);
    }

    public function repeating(int $cycles = 3): static
    {
        return $this->state([
            'duration' => DiscountDurationEnum::Repeating,
            'duration_cycles' => $cycles,
        ]);
    }
}
