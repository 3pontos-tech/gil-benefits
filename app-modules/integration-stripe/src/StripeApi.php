<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe;

use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Stripe\Subscription;
use Stripe\Util\ApiVersion;
use TresPontosTech\IntegrationStripe\DTO\CheckoutSessionDTO;
use TresPontosTech\IntegrationStripe\DTO\CouponDTO;

/**
 * The only class that calls Stripe. Keeps the surface the adapter, the webhook handler
 * and the plan command need, so tests swap it for FakeStripeApi and never reach the network.
 */
class StripeApi
{
    public function __construct(
        private readonly StripeClient $client,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     */
    public function createCustomer(array $params): string
    {
        return $this->client->customers->create($params)->id;
    }

    public function createCheckoutSession(CheckoutSessionDTO $session): Session
    {
        return $this->client->checkout->sessions->create($session->toArray());
    }

    public function retrieveCheckoutSession(string $id): Session
    {
        return $this->client->checkout->sessions->retrieve($id);
    }

    public function createCoupon(CouponDTO $coupon): string
    {
        return $this->client->coupons->create($coupon->toArray())->id;
    }

    public function createPortalSession(string $customerId, string $returnUrl, ?string $configuration): string
    {
        return $this->client->billingPortal->sessions->create(array_filter([
            'customer' => $customerId,
            'return_url' => $returnUrl,
            'configuration' => $configuration,
        ]))->url;
    }

    /**
     * Pinned to the SDK's API version, so the payloads keep the shape the handler reads.
     *
     * @param  list<string>  $events
     * @return array{id: string, secret: string}
     */
    public function createWebhookEndpoint(string $url, array $events): array
    {
        $endpoint = $this->client->webhookEndpoints->create([
            'url' => $url,
            'enabled_events' => $events,
            'api_version' => ApiVersion::CURRENT,
        ]);

        return ['id' => $endpoint->id, 'secret' => (string) $endpoint->secret];
    }

    public function retrieveSubscription(string $id): Subscription
    {
        return $this->client->subscriptions->retrieve($id);
    }

    public function cancelAtPeriodEnd(string $subscriptionId): void
    {
        $this->client->subscriptions->update($subscriptionId, ['cancel_at_period_end' => true]);
    }

    /**
     * @param  array<string, string>  $metadata
     */
    public function createProduct(string $name, array $metadata): string
    {
        return $this->client->products->create(['name' => $name, 'metadata' => $metadata])->id;
    }

    /**
     * A monthly recurring price.
     *
     * @param  array<string, string>  $metadata
     */
    public function createMonthlyPrice(string $productId, int $unitAmount, string $currency, array $metadata): string
    {
        return $this->client->prices->create([
            'product' => $productId,
            'currency' => $currency,
            'unit_amount' => $unitAmount,
            'recurring' => ['interval' => 'month'],
            'metadata' => $metadata,
        ])->id;
    }
}
