<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Contracts;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Company\Models\Company;

interface BillingContract
{
    public function ensureCustomerExists(Company|User $billable): void;

    public function isSubscribed(Company|User $billable, string $planSlug): bool;

    public function hasActivePlan(Company $company): bool;

    public function createCheckout(Company|User $billable, CheckoutData $data): string;

    public function getBillingPortalUrl(Company|User $billable, string $returnUrl, array $options = []): string;

    public function hasActiveSubscription(Company|User $billable): bool;
}
