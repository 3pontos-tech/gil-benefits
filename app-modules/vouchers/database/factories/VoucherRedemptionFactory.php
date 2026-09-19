<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Database\Factories;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Vouchers\Models\VoucherCode;
use TresPontosTech\Vouchers\Models\VoucherRedemption;

/** @extends Factory<VoucherRedemption> */
class VoucherRedemptionFactory extends Factory
{
    protected $model = VoucherRedemption::class;

    public function definition(): array
    {
        return [
            'voucher_code_id' => VoucherCode::factory(),
            'user_id' => User::factory(),
            'redeemed_at' => now(),
        ];
    }
}
