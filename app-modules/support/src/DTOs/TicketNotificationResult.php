<?php

declare(strict_types=1);

namespace TresPontosTech\Support\DTOs;

use TresPontosTech\Support\Enums\TicketNotificationStatusEnum;

final readonly class TicketNotificationResult
{
    public function __construct(
        public TicketNotificationStatusEnum $status,
        public ?int $responseCode = null,
        public ?string $error = null,
    ) {}

    public static function sent(int $responseCode): self
    {
        return new self(TicketNotificationStatusEnum::Sent, $responseCode);
    }

    public static function failed(?int $responseCode, string $error): self
    {
        return new self(TicketNotificationStatusEnum::Failed, $responseCode, $error);
    }

    public static function skipped(string $reason): self
    {
        return new self(TicketNotificationStatusEnum::Skipped, error: $reason);
    }
}
