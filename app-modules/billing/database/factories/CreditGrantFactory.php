<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Billing\Core\Models\CreditGrant;
use TresPontosTech\Company\Models\Company;

/** @extends Factory<CreditGrant> */
class CreditGrantFactory extends Factory
{
    protected $model = CreditGrant::class;

    public function definition(): array
    {
        return [
            'admin_user_id' => User::factory(),
            'company_id' => Company::factory(),
            'target_user_id' => null,
            'quantity' => $this->faker->numberBetween(1, 10),
            'justification' => $this->faker->sentence(),
        ];
    }

    public function forUser(User $user): self
    {
        return $this->state([
            'target_user_id' => $user->getKey(),
        ]);
    }
}
