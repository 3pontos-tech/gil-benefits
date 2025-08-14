<?php

namespace Database\Factories\Plans;

use App\Enums\PlanTypeEnum;
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
            'price' => $this->faker->numberBetween(100, 200),
            'type' => $this->faker->randomElement(PlanTypeEnum::cases()),
            'hours_included' => $this->faker->numberBetween(4, 5),
            'description' => $this->faker->text(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
