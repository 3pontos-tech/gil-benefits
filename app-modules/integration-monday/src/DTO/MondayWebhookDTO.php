<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\DTO;

/**
 * Parsed Monday webhook payload. Monday wraps everything under an `event` key;
 * status columns carry the new label index under `value.label.index`.
 */
final readonly class MondayWebhookDTO
{
    public function __construct(
        public string $type,
        public string $boardId,
        public string $itemId,
        public string $columnId,
        public ?int $index,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        $event = $payload['event'] ?? [];
        $index = $event['value']['label']['index'] ?? null;

        return new self(
            type: (string) $event['type'],
            boardId: (string) $event['boardId'],
            itemId: (string) $event['pulseId'],
            columnId: (string) $event['columnId'],
            index: $index === null ? null : (int) $index,
        );
    }
}
