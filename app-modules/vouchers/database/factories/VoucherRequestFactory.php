<?php

namespace TresPontosTech\Vouchers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
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
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),

            'company_id' => Company::factory(),
        ];
    }
}
