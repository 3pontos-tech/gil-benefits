<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use TresPontosTech\Vouchers\Models\VoucherBatch;
use TresPontosTech\Vouchers\Models\VoucherCode;
use TresPontosTech\Vouchers\Support\VoucherCodeGenerator;

/** @extends Factory<VoucherCode> */
class VoucherCodeFactory extends Factory
{
    protected $model = VoucherCode::class;

    public function definition(): array
    {
        return [
            'voucher_batch_id' => VoucherBatch::factory(),
            'code' => VoucherCodeGenerator::generate(),
            'max_redemptions' => 1,
            'redemptions_count' => 0,
        ];
    }

    public function exhausted(): self
    {
        return $this->state(fn (array $attributes): array => [
            'redemptions_count' => $attributes['max_redemptions'] ?? 1,
        ]);
    }
}
