<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\Enums;

/**
 * The events the handler translates. The same list is registered on the endpoint by
 * `stripe:webhook:create` and forwarded by `make stripe-listen`, so a handled event is
 * never left out of the subscription.
 */
enum StripeWebhookEventEnum: string
{
    case SubscriptionCreated = 'customer.subscription.created';
    case SubscriptionUpdated = 'customer.subscription.updated';
    case SubscriptionDeleted = 'customer.subscription.deleted';
    case CheckoutCompleted = 'checkout.session.completed';
    case CheckoutAsyncPaymentSucceeded = 'checkout.session.async_payment_succeeded';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $event): string => $event->value, self::cases());
    }
}
