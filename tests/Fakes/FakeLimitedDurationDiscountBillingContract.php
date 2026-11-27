<?php

declare(strict_types=1);

namespace Tests\Fakes;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Contracts\SupportsCheckoutDiscount;
use TresPontosTech\Billing\Core\Contracts\SupportsLimitedDurationDiscount;
use TresPontosTech\Billing\Core\DTOs\BillingPortal;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Company\Models\Company;

final class FakeLimitedDurationDiscountBillingContract implements BillingContract, SupportsCheckoutDiscount, SupportsLimitedDurationDiscount
{
    public function ensureCustomerExists(Company|User $billable): void {}

    public function createCheckout(Company|User $billable, CheckoutData $data): string
    {
        return 'https://checkout.test';
    }

    public function checkoutOpensInNewTab(): bool
    {
        return false;
    }

    public function billingPortal(Company|User $billable, string $returnUrl): BillingPortal
    {
        return BillingPortal::external('https://billing.test');
    }
}
