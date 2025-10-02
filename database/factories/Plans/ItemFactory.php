<?php

namespace Database\Factories\Plans;

use App\Enums\PlanTypeEnum;
use App\Models\Plans\Item;
use App\Models\Plans\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'price' => $this->faker->randomNumber(),
            'type' => $this->faker->randomElement(PlanTypeEnum::cases()),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'plan_id' => Plan::factory(),
        ];
    }
}
