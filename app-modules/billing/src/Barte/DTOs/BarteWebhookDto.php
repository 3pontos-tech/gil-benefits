<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte\DTOs;

use Illuminate\Support\Collection;
use TresPontosTech\Billing\Barte\Enums\BarteWebhookEventEnum;

readonly class BarteWebhookDto
{
    public function __construct(
        public string $uuid,
        public string $domain,
        public ?BarteWebhookEventEnum $event,
        public ?string $uuidBuyer,
        public Collection $metadata,
    ) {}

    public static function fromArray(array $payload): self
    {
        return new self(
            uuid: $payload['uuid'],
            domain: $payload['domain'],
            event: BarteWebhookEventEnum::tryFrom($payload['status']),
            uuidBuyer: $payload['uuidBuyer'] ?? null,
            metadata: collect($payload['metadata'] ?? [])->pluck('value', 'key'),
        );
    }
}
