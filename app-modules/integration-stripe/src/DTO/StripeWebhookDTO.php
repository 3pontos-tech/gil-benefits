<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\DTO;

/**
 * What the handler keeps from an event: which object changed. The object itself is read
 * again from the API, so its state in the payload is never trusted.
 */
final readonly class StripeWebhookDTO
{
    public function __construct(
        public string $eventId,
        public string $type,
        public string $objectId,
        public int $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromPayload(array $payload): self
    {
        return new self(
            eventId: (string) ($payload['id'] ?? ''),
            type: (string) ($payload['type'] ?? ''),
            objectId: (string) data_get($payload, 'data.object.id', ''),
            createdAt: (int) ($payload['created'] ?? 0),
        );
    }
}
