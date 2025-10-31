<?php

namespace TresPontosTech\Billing\Core;

class Price
{
    public function __construct(
        public string $type,
        public string $productId,
        public array $metadata = []
    ) {}
}
