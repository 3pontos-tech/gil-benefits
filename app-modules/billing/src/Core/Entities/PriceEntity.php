<?php

namespace TresPontosTech\Billing\Core\Entities;

class PriceEntity implements \JsonSerializable
{
    public function __construct(
        public string $type,
        public string $priceId,
        public int $priceInCents = 0,
        public int $monthlyAppointments = 1,
        public bool $whatsappEnabled = false,
        public bool $materialsEnabled = true,
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
            priceInCents: $payload['unit_amount_decimal'] ?? 0,
            monthlyAppointments: $payload['monthly_appointments'] ?? 1,
            whatsappEnabled: $payload['whatsapp_enabled'] ?? false,
            materialsEnabled: $payload['materials_enabled'] ?? true,
            metadata: is_string($payload['metadata']) ? json_decode($payload['metadata'], true) : $payload['metadata']
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'type' => $this->type,
            'price_id' => $this->priceId,
            'unit_amount_decimal' => $this->priceInCents,
            'metadata' => $this->metadata,
        ];
    }
}
