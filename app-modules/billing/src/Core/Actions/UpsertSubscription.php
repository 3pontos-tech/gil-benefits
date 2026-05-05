<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions;

use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;
use TresPontosTech\Billing\Core\Models\Subscriptions\Subscription;

final class UpsertSubscription
{
    public function handle(SubscriptionDTO $dto): void
    {
        Subscription::query()->updateOrCreate(
            ['stripe_id' => $dto->subscriptionExternalId],
            [
                'subscriptionable_type' => $dto->billableType,
                'subscriptionable_id' => $dto->billableId,
                'type' => 'default',
                'stripe_status' => $dto->status,
                'stripe_price' => $dto->planExternalId,
                'quantity' => $dto->quantity,
                'ends_at' => $dto->endsAt,
            ]
        );
    }
}
