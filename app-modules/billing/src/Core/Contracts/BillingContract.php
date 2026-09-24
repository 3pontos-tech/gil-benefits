<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Contracts;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Company\Models\Company;

/**
 * What every payment provider must do: sell a subscription and answer whether a
 * billable has one.
 *
 * Anything a provider might not be able to do lives in its own interface —
 * SupportsCreditPurchase (credits module), SupportsSubscriptionCancellation — so
 * a driver declares its capabilities instead of implementing a method that throws.
 */
interface BillingContract
{
    public function ensureCustomerExists(Company|User $billable): void;

    public function isSubscribed(Company|User $billable, string $planSlug): bool;

    public function createCheckout(Company|User $billable, CheckoutData $data): string;

    public function checkoutOpensInNewTab(): bool;

    /**
     * @param  array<string, mixed>  $options
     */
    public function getBillingPortalUrl(Company|User $billable, string $returnUrl, array $options = []): string;

    public function hasActiveSubscription(Company|User $billable): bool;
}
