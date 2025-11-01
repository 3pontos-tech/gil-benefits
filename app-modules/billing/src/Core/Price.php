<?php

namespace TresPontosTech\Billing\Core;

class Price implements \JsonSerializable
{
    public function __construct(
        public string $type,
        public string $priceId,
        public array $metadata = []
    ) {}

    public static function make(array $payload): self
    {
        return new self(
            type: $payload['type'],
            priceId: $payload['price_id'],
            metadata: $payload['metadata'] ?? []
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'type' => $this->type,
            'price_id' => $this->priceId,
            'metadata' => $this->metadata,
        ];
    }
}
