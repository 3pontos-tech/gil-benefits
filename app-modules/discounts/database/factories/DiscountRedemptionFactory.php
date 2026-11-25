<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Enums\DiscountRedemptionStatusEnum;
use TresPontosTech\Discounts\Enums\DiscountValueTypeEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountRedemption;

/** @extends Factory<DiscountRedemption> */
class DiscountRedemptionFactory extends Factory
{
    protected $model = DiscountRedemption::class;

    public function definition(): array
    {
        return [
            'discount_id' => Discount::factory(),
            'discount_grant_id' => null,
            'billable_type' => (new User)->getMorphClass(),
            'billable_id' => User::factory(),
            'plan_slug' => 'plano-essencial',
            'subscription_external_id' => 'checkout_' . Str::random(20),
            'status' => DiscountRedemptionStatusEnum::Reserved,
            'value_type' => DiscountValueTypeEnum::Percent,
            'percent_off' => 30,
            'amount_off_cents' => null,
            'duration' => DiscountDurationEnum::Forever,
            'duration_cycles' => null,
            'list_amount_cents' => 10000,
            'discounted_amount_cents' => 7000,
            'cycles_applied' => 0,
            'reserved_until' => now()->addHours(2),
            'confirmed_at' => null,
            'completed_at' => null,
        ];
    }

    public function reserved(): static
    {
        return $this->state([
            'status' => DiscountRedemptionStatusEnum::Reserved,
            'reserved_until' => now()->addHours(2),
        ]);
    }

    public function active(): static
    {
        return $this->state([
            'status' => DiscountRedemptionStatusEnum::Active,
            'reserved_until' => null,
            'confirmed_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state([
            'status' => DiscountRedemptionStatusEnum::Completed,
            'reserved_until' => null,
            'confirmed_at' => now()->subMonths(3),
            'completed_at' => now(),
        ]);
    }

    public function released(): static
    {
        return $this->state([
            'status' => DiscountRedemptionStatusEnum::Released,
            'reserved_until' => null,
        ]);
    }

    public function canceled(): static
    {
        return $this->state([
            'status' => DiscountRedemptionStatusEnum::Canceled,
            'reserved_until' => null,
        ]);
    }

    public function forBillable(User $user): static
    {
        return $this->state([
            'billable_type' => $user->getMorphClass(),
            'billable_id' => $user->getKey(),
        ]);
    }
}
