<?php

namespace Tests\Fakes;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Company\Models\Company;

final class FakeBillingContract implements BillingContract
{
    public function __construct(
        private readonly bool $isSubscribed = false,
        private readonly bool $hasActiveSubscription = false,
        private readonly bool $hasActivePlan = false,
        private readonly string $checkoutUrl = 'https://checkout.test',
        private readonly string $billingPortalUrl = 'https://billing.test',
        private readonly ?\Closure $createCheckoutUsing = null,
    ) {}

    public function ensureCustomerExists(Company|User $billable): void {}

    public function isSubscribed(Company|User $billable, string $planSlug): bool
    {
        return $this->isSubscribed;
    }

    public function hasActivePlan(Company $company): bool
    {
        return $this->hasActivePlan;
    }

    public function createCheckout(Company|User $billable, CheckoutData $data): string
    {
        if ($this->createCheckoutUsing !== null) {
            return ($this->createCheckoutUsing)($billable, $data);
        }

        return $this->checkoutUrl;
    }

    public function getBillingPortalUrl(Company|User $billable, string $returnUrl, array $options = []): string
    {
        return $this->billingPortalUrl;
    }

    public function hasActiveSubscription(Company|User $billable): bool
    {
        return $this->hasActiveSubscription;
    }

    public function cancelSubscription(Company|User $billable): void {}
}
