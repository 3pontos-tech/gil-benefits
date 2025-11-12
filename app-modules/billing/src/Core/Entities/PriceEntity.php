<?php

namespace TresPontosTech\Billing\Core\Entities;

class PriceEntity implements \JsonSerializable
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

    public static function fromEloquent(array $payload): self
    {
        return new self(
            type: $payload['type'],
            priceId: $payload['provider_price_id'],
            metadata: is_string($payload['metadata']) ? json_decode($payload['metadata'], true) : $payload['metadata']
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
