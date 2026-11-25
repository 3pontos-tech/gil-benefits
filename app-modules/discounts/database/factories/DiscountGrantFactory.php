<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use TresPontosTech\Discounts\Enums\DiscountGrantRevocationReasonEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;

/** @extends Factory<DiscountGrant> */
class DiscountGrantFactory extends Factory
{
    protected $model = DiscountGrant::class;

    public function definition(): array
    {
        return [
            'discount_id' => Discount::factory(),
            'user_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'phone_number' => '5511999999999',
            'source' => 'clau',
            'external_ref' => (string) Str::uuid(),
            'tax_id' => fake()->cpf(false),
            'consent_term_version' => '1',
            'consent_accepted_at' => now(),
            'expires_at' => now()->addDays(180),
        ];
    }

    public function claimedBy(User $user): static
    {
        return $this->state([
            'user_id' => $user->getKey(),
            'claimed_at' => now(),
            'email' => $user->email,
        ]);
    }

    public function revoked(): static
    {
        return $this->state([
            'revoked_at' => now(),
            'revoked_reason' => DiscountGrantRevocationReasonEnum::Manual,
        ]);
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subDay()]);
    }
}
