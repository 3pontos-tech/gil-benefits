<?php

namespace Database\Factories\Users;

use Illuminate\Support\Facades\Date;
use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Company\Models\Company;

class DetailFactory extends Factory
{
    protected $model = Detail::class;

    public function definition(): array
    {
        return [
            'document_id' => $this->faker->rg(),
            'tax_id' => $this->faker->cpf(),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),

            'user_id' => User::factory(),
            'company_id' => Company::factory(),
        ];
    }
}
