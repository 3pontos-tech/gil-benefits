<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Listeners;

use TresPontosTech\Billing\Core\Actions\UpsertSubscription;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionActivated;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCancelled;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionCreated;
use TresPontosTech\Billing\Core\Events\Subscription\SubscriptionDefaulted;

class SyncSubscriptionOnStatusChange
{
    public function __construct(private readonly UpsertSubscription $action) {}

    public function handleCreated(SubscriptionCreated $event): void
    {
        $this->action->handle($event->dto);
    }

    public function handleActivated(SubscriptionActivated $event): void
    {
        $this->action->handle($event->dto);
    }

    public function handleDefaulted(SubscriptionDefaulted $event): void
    {
        $this->action->handle($event->dto);
    }

    public function handleCancelled(SubscriptionCancelled $event): void
    {
        $this->action->handle($event->dto);
    }
}
