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
        public string $planSlug,
        public int $quantity,
        public ?Carbon $endsAt,
    ) {}

    public static function make(
        BillingCustomer $billingCustomer,
        string $subscriptionExternalId,
        string $status,
        ?string $planUuid,
        ?string $cycleType,
        int|string $quantity,
        ?Carbon $endsAt = null,
        string $planSlug = 'default',
        ?string $priceId = null,
    ): self {
        return new self(
            billableType: $billingCustomer->billable_type,
            billableId: $billingCustomer->billable_id,
            subscriptionExternalId: $subscriptionExternalId,
            status: $status,
            // Persist the exact price the buyer checked out with (e.g. flamma's
            // standalone-user price) so tenant-specific pricing is not collapsed onto
            // the shared plan id. Falls back to the plan id when no price was provided.
            planExternalId: $priceId ?? ($planUuid ? ($cycleType ? sprintf('%s-%s', $planUuid, $cycleType) : $planUuid) : null),
            planSlug: $planSlug,
            quantity: (int) $quantity,
            endsAt: $endsAt,
        );
    }
}
