<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Events\Credit;

final class OrderCreditPurchased
{
    public function __construct(
        public readonly string $orderUuid,
        public readonly string $billableType,
        public readonly string $billableId,
        public readonly string $companyId,
        public readonly int $quantity,
    ) {}
}
