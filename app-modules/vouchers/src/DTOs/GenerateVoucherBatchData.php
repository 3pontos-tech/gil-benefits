<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\DTOs;

use Illuminate\Support\Carbon;

final readonly class GenerateVoucherBatchData
{
    public function __construct(
        public string $companyPlanId,
        public string $name,
        public int $quantity,
        public ?Carbon $expiresAt = null,
        public ?string $createdBy = null,
        public ?string $notes = null,
        public int $maxRedemptions = 1,
    ) {}
}
