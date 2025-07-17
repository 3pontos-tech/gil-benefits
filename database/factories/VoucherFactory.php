<?php

namespace Database\Factories;

use App\Models\Companies\Company;
use App\Models\Consultant;
use App\Models\Users\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class VoucherFactory extends Factory
{
    protected $model = Voucher::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->word(),
            'status' => $this->faker->word(),
            'valid_until' => $this->faker->dateTimeBetween('-1 month', '+1 month'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
            'consultant_id' => Consultant::factory(),
            'user_id' => User::factory(),
        ];
    }
}
