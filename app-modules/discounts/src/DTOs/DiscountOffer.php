<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\DTOs;

use Illuminate\Support\Carbon;
use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;

final readonly class DiscountOffer
{
    public function __construct(
        public string $key,
        public string $label,
        public CheckoutDiscount $checkoutDiscount,
        public int $listAmountCents,
        public int $discountedAmountCents,
        public int $savingPerCycleCents,
        public int $score,
        public ?Carbon $expiresAt,
    ) {}
}
