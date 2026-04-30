<?php

declare(strict_types=1);

namespace TresPontosTech\User\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\User\Enums\LifeMoment;
use TresPontosTech\User\Models\UserAnamnese;

/**
 * @extends Factory<UserAnamnese>
 */
class UserAnamneseFactory extends Factory
{
    protected $model = UserAnamnese::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'life_moment' => $this->faker->randomElement(LifeMoment::cases()),
            'main_motivation' => $this->faker->paragraph(),
            'money_relationship' => $this->faker->paragraph(),
            'plans_monthly_expenses' => $this->faker->paragraph(),
            'tried_financial_strategies' => $this->faker->paragraph(),
        ];
    }
}
