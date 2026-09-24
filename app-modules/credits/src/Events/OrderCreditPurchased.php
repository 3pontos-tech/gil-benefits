<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Events;

final class OrderCreditPurchased
{
    public function __construct(
        public readonly string $creditOrderId,
    ) {}
}
