<?php

declare(strict_types=1);

namespace TresPontosTech\Support\DTOs;

use JsonSerializable;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;

/**
 * Outcome of sending a ticket to a single destination. Channel senders return
 * this; the orchestrator persists it onto the TicketDestination. Senders never
 * touch the database.
 */
final readonly class DispatchResult implements JsonSerializable
{
    public function __construct(
        public TicketDestinationStatusEnum $status,
        public ?string $referenceId = null,
        public ?string $error = null,
    ) {}

    public static function sent(?string $referenceId = null): self
    {
        return new self(TicketDestinationStatusEnum::Sent, $referenceId);
    }

    public static function pending(): self
    {
        return new self(TicketDestinationStatusEnum::Pending);
    }

    public static function failed(string $error): self
    {
        return new self(TicketDestinationStatusEnum::Failed, error: $error);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'status' => $this->status,
            'reference_id' => $this->referenceId,
        ];
    }
}
