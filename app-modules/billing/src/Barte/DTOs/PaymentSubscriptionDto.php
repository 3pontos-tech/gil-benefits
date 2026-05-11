<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte\DTOs;

readonly class PaymentSubscriptionDto
{
    public function __construct(
        public int $idPlan,
        public float $valuePerMonth,
        public string $type = 'MONTHLY',
    ) {}

    public function toArray(): array
    {
        return [
            'idPlan' => $this->idPlan,
            'type' => $this->type,
            'valuePerMonth' => $this->valuePerMonth,
        ];
    }
}
