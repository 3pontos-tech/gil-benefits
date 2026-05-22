<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\DTOs;

final readonly class CreditDTO
{
    public function __construct(
        public string|int $holderId,
        public string|int|null $ownerId = null,
        public string|int|null $companyId = null,
        public string|int|null $appointmentId = null,
        public int $quantity = 1,
    ) {}
}
