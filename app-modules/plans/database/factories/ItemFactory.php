<?php

namespace TresPontosTech\Plans\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Plans\Enums\PlanTypeEnum;
use TresPontosTech\Plans\Models\Item;
use TresPontosTech\Plans\Models\Plan;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition(): array
    {
        return [
            'price' => $this->faker->randomNumber(),
            'type' => $this->faker->randomElement(PlanTypeEnum::cases()),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),

            'plan_id' => Plan::factory(),
        ];
    }
}
