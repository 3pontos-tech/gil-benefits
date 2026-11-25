<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\DTOs;

use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;

final readonly class DiscountCandidate
{
    public function __construct(
        public Discount $discount,
        public ?DiscountGrant $grant = null,
    ) {}
}
