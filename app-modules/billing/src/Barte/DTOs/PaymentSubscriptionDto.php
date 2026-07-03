<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte\DTOs;

readonly class PaymentSubscriptionDto
{
    public function __construct(
        public int|string $uuidPlan,
        public float $valuePerMonth,
        public string $type = 'MONTHLY',
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'uuidPlan' => $this->uuidPlan,
            'type' => $this->type,
            'valuePerMonth' => $this->valuePerMonth,
        ];
    }
}
