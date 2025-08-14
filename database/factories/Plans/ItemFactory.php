<?php

namespace Database\Factories\Plans;

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
            'name' => $this->faker->name(),
            'price' => $this->faker->randomNumber(),
            'type' => $this->faker->word(),
            'quantity' => $this->faker->numberBetween(1, 30),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'plan_id' => Plan::factory(),
        ];
    }
}
