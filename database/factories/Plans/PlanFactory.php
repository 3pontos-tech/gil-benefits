<?php

namespace Database\Factories\Plans;

use App\Models\Plans\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
