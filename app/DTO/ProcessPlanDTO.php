<?php

namespace App\DTO;

use DateTimeInterface;

class ProcessPlanDTO
{
    public function __construct(
        public string|int $companyId,
        public int $itemId,
        public string $status,
        public DateTimeInterface $subscriptionStartingAt,
    ) {}

    public static function make(string|int $companyId, array $data): self
    {
        return new self(
            companyId: $companyId,
            itemId: $data['item_id'],
            status: $data['status'],
            subscriptionStartingAt: $data['subscription_starting_at'],
        );
    }
}
