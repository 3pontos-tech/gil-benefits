<?php

namespace TresPontosTech\Company\Database\Factories;

use Illuminate\Support\Facades\Date;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Company\Models\Company;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'slug' => $this->faker->slug(),
            'tax_id' => $this->faker->cnpj(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),

            'user_id' => User::factory(),
        ];
    }
}
