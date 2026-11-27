<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * The webhook answered 200 before the work ran, so Stripe will not send this event again:
 * after the last retry, only someone re-running the failed job brings the change in.
 */
final readonly class StripeWebhookFailed
{
    use Dispatchable;

    public function __construct(
        public string $eventId,
        public string $type,
    ) {}
}
