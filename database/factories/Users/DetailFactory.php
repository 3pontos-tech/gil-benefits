<?php

namespace Database\Factories\Users;

use App\Models\Users\Detail;
use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use TresPontosTech\Tenant\Models\Company;

class DetailFactory extends Factory
{
    protected $model = Detail::class;

    public function definition(): array
    {
        return [
            'document_id' => $this->faker->rg(),
            'tax_id' => $this->faker->cpf(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'user_id' => User::factory(),
            'company_id' => Company::factory(),
        ];
    }
}
