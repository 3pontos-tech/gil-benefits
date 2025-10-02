<?php

namespace TresPontosTech\Plans\Database\Factories;

use App\Enums\PlanTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
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
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'plan_id' => Plan::factory(),
        ];
    }
}
