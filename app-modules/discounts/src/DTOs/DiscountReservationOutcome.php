<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\DTOs;

use TresPontosTech\Discounts\Enums\DiscountIneligibilityReasonEnum;
use TresPontosTech\Discounts\Enums\DiscountReservationStatusEnum;
use TresPontosTech\Discounts\Models\DiscountGrant;

final readonly class DiscountReservationOutcome
{
    public function __construct(
        public DiscountReservationStatusEnum $status,
        public ?DiscountIneligibilityReasonEnum $reason = null,
        public ?DiscountGrant $grant = null,
        public bool $created = false,
    ) {}

    public static function reserved(DiscountGrant $grant, bool $created): self
    {
        return new self(DiscountReservationStatusEnum::Reserved, grant: $grant, created: $created);
    }

    public static function ineligible(DiscountIneligibilityReasonEnum $reason): self
    {
        return new self(DiscountReservationStatusEnum::Ineligible, reason: $reason);
    }

    public static function conflict(): self
    {
        return new self(DiscountReservationStatusEnum::Conflict);
    }
}
