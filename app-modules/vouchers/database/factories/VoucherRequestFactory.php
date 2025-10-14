<?php

namespace TresPontosTech\Vouchers\Database\Factories;

use Illuminate\Support\Facades\Date;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Vouchers\Models\VoucherRequest;

class VoucherRequestFactory extends Factory
{
    protected $model = VoucherRequest::class;

    public function definition(): array
    {
        return [
            'quantity' => $this->faker->randomNumber(),
            'status' => $this->faker->randomElement(['pending', 'approved', 'rejected']),
            'created_at' => Date::now(),
            'updated_at' => Date::now(),

            'company_id' => Company::factory(),
        ];
    }
}
