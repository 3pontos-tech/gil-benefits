<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\DTOs;

use Illuminate\Support\Carbon;
use TresPontosTech\Billing\Core\Models\BillingCustomer;

final readonly class SubscriptionDTO
{
    public function __construct(
        public string $billableType,
        public int|string $billableId,
        public string $subscriptionExternalId,
        public string $status,
        public ?string $planExternalId,
        public null|int|string $quantity,
        public ?Carbon $endsAt,
    ) {}

    public static function make(
        BillingCustomer $billingCustomer,
        string $subscriptionExternalId,
        string $status,
        ?string $planUuid,
        ?string $cycleType,
        null|int|string $quantity,
        ?Carbon $endsAt = null,
    ): self {
        return new self(
            billableType: $billingCustomer->billable_type,
            billableId: $billingCustomer->billable_id,
            subscriptionExternalId: $subscriptionExternalId,
            status: $status,
            planExternalId: $planUuid && $cycleType ? sprintf('%s-%s', $planUuid, $cycleType) : null,
            quantity: (int) $quantity,
            endsAt: $endsAt,
        );
    }
}
