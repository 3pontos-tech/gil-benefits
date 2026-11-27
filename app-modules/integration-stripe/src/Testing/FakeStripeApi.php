<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\Testing;

use Stripe\Checkout\Session;
use Stripe\Subscription;
use Throwable;
use TresPontosTech\IntegrationStripe\DTO\CheckoutSessionDTO;
use TresPontosTech\IntegrationStripe\DTO\CouponDTO;
use TresPontosTech\IntegrationStripe\StripeApi;

/**
 * In-memory StripeApi for tests: records every call and answers with canned objects.
 *
 *   $this->app->instance(StripeApi::class, $fake = new FakeStripeApi);
 */
final class FakeStripeApi extends StripeApi
{
    /** @var list<array<string, mixed>> */
    public array $customers = [];

    /** @var list<CheckoutSessionDTO> */
    public array $checkoutSessions = [];

    /** @var list<CouponDTO> */
    public array $coupons = [];

    /** @var list<array{customer: string, return_url: string, configuration: ?string}> */
    public array $portalSessions = [];

    /** @var list<string> */
    public array $cancelledAtPeriodEnd = [];

    /** @var list<array{name: string, metadata: array<string, string>}> */
    public array $products = [];

    /** @var list<array<string, mixed>> */
    public array $prices = [];

    /** @var list<array{url: string, events: list<string>}> */
    public array $webhookEndpoints = [];

    /** @var array<string, array<string, mixed>> */
    public array $subscriptions = [];

    /** @var array<string, array<string, mixed>> */
    public array $sessions = [];

    /** Thrown once by the next read, to exercise retries. */
    public ?Throwable $nextReadFails = null;

    private int $sequence = 0;

    public function __construct() {}

    public function createCustomer(array $params): string
    {
        $this->customers[] = $params;

        return 'cus_fake' . $this->next();
    }

    public function createCheckoutSession(CheckoutSessionDTO $session): Session
    {
        $this->checkoutSessions[] = $session;
        $id = 'cs_fake' . $this->next();

        return Session::constructFrom(['id' => $id, 'url' => 'https://checkout.stripe.test/' . $id]);
    }

    public function retrieveCheckoutSession(string $id): Session
    {
        $this->failIfAsked();

        return Session::constructFrom($this->sessions[$id] + ['id' => $id]);
    }

    public function createCoupon(CouponDTO $coupon): string
    {
        $this->coupons[] = $coupon;

        return 'coupon_fake' . $this->next();
    }

    public function createPortalSession(string $customerId, string $returnUrl, ?string $configuration): string
    {
        $this->portalSessions[] = ['customer' => $customerId, 'return_url' => $returnUrl, 'configuration' => $configuration];

        return 'https://billing.stripe.test/' . $customerId;
    }

    public function createWebhookEndpoint(string $url, array $events): array
    {
        $this->webhookEndpoints[] = ['url' => $url, 'events' => $events];

        return ['id' => 'we_fake' . $this->next(), 'secret' => 'whsec_fake'];
    }

    public function retrieveSubscription(string $id): Subscription
    {
        $this->failIfAsked();

        return Subscription::constructFrom($this->subscriptions[$id] + ['id' => $id]);
    }

    public function cancelAtPeriodEnd(string $subscriptionId): void
    {
        $this->cancelledAtPeriodEnd[] = $subscriptionId;
    }

    public function createProduct(string $name, array $metadata): string
    {
        $this->products[] = ['name' => $name, 'metadata' => $metadata];

        return 'prod_fake' . $this->next();
    }

    public function createMonthlyPrice(string $productId, int $unitAmount, string $currency, array $metadata): string
    {
        $this->prices[] = ['product' => $productId, 'unit_amount' => $unitAmount, 'currency' => $currency, 'metadata' => $metadata];

        return 'price_fake' . $this->next();
    }

    private function failIfAsked(): void
    {
        $error = $this->nextReadFails;
        $this->nextReadFails = null;

        throw_if($error instanceof Throwable, $error);
    }

    private function next(): string
    {
        return (string) ++$this->sequence;
    }
}
