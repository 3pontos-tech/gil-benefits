<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Events\Subscription;

use TresPontosTech\Billing\Core\DTOs\SubscriptionDTO;

final class SubscriptionActivated
{
    public function __construct(
        public readonly SubscriptionDTO $dto,
    ) {}
}
