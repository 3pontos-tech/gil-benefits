<?php

namespace TresPontosTech\Company\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Date;
use TresPontosTech\Company\Models\Company;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $randomId = rand(100, 999);
        $name = 'Test Company ' . $randomId;
        return [
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'tax_id' => '12.345.678/0001-90',
            'created_at' => Date::now(),
            'updated_at' => Date::now(),

            'user_id' => User::factory(),
        ];
    }
}
