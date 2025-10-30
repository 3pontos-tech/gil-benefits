<?php

namespace TresPontosTech\Plans\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Plans\Models\Plan;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['gold', 'platinum', 'diamond']),
            'suggested_employees_count' => $this->faker->numberBetween(10, 20),
            'hours_included' => $this->faker->numberBetween(4, 5),
            'description' => $this->faker->text(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
