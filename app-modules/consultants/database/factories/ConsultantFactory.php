<?php

namespace TresPontosTech\Consultants\Database\Factories;

use Illuminate\Support\Facades\Date;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Consultants\Models\Consultant;

class ConsultantFactory extends Factory
{
    protected $model = Consultant::class;

    public function definition(): array
    {
        $name = $this->faker->firstName() . ' ' . $this->faker->lastName();

        return [
            'name' => $name,
            'phone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'short_description' => $this->faker->sentence(),
            'slug' => str($name)->slug(),
            'biography' => $this->faker->paragraph(),
            'readme' => $this->faker->paragraph(),
            'socials_urls' => [],
            'created_at' => Date::now(),
            'updated_at' => Date::now(),
        ];
    }
}
