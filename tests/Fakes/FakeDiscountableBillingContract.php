<?php

namespace Tests\Fakes;

use App\Models\Users\User;
use Closure;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Contracts\SupportsCheckoutDiscount;
use TresPontosTech\Billing\Core\DTOs\BillingPortal;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Company\Models\Company;

final class FakeDiscountableBillingContract implements BillingContract, SupportsCheckoutDiscount
{
    public function __construct(
        private readonly bool $isSubscribed = false,
        private readonly bool $hasActiveSubscription = false,
        private readonly string $checkoutUrl = 'https://checkout.test',
        private readonly string $billingPortalUrl = 'https://billing.test',
        private readonly ?Closure $createCheckoutUsing = null,
    ) {}

    public function ensureCustomerExists(Company|User $billable): void {}

    public function isSubscribed(Company|User $billable, string $planSlug): bool
    {
        return $this->isSubscribed;
    }

    public function createCheckout(Company|User $billable, CheckoutData $data): string
    {
        if ($this->createCheckoutUsing instanceof Closure) {
            return ($this->createCheckoutUsing)($billable, $data);
        }

        return $this->checkoutUrl;
    }

    public function checkoutOpensInNewTab(): bool
    {
        return false;
    }

    public function billingPortal(Company|User $billable, string $returnUrl): BillingPortal
    {
        return BillingPortal::external($this->billingPortalUrl);
    }

    public function hasActiveSubscription(Company|User $billable): bool
    {
        return $this->hasActiveSubscription;
    }
}
