<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\DTOs;

final readonly class CheckoutData
{
    public function __construct(
        public string $planSlug,
        public string $priceId,
        public bool $isMetered,
        public int $quantity,
        public ?int $trialDays,
        public bool $allowPromotionCodes,
        public bool $collectTaxIds,
        public string $successUrl,
        public string $cancelUrl,
        public array $metadata = [],
    ) {}
}
