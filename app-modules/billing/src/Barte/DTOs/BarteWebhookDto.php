<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte\DTOs;

use Illuminate\Support\Collection;
use TresPontosTech\Billing\Barte\Enums\BarteWebhookEventEnum;

readonly class BarteWebhookDto
{
    /**
     * @param  Collection<string, mixed>  $metadata
     */
    public function __construct(
        public string $uuid,
        public string $domain,
        public ?BarteWebhookEventEnum $event,
        public ?string $uuidBuyer,
        public Collection $metadata,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        /** @var list<array<string, mixed>> $metadata */
        $metadata = $payload['metadata'] ?? [];

        return new self(
            uuid: $payload['uuid'],
            domain: $payload['domain'],
            event: BarteWebhookEventEnum::tryFrom($payload['status']),
            uuidBuyer: $payload['uuidBuyer'] ?? null,
            metadata: collect($metadata)->pluck('value', 'key'),
        );
    }
}
