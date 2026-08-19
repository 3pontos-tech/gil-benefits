<?php

declare(strict_types=1);

use App\Models\Users\User;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Contracts\SupportsCreditPurchase;
use TresPontosTech\Billing\Core\DTOs\CheckoutData;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Actions\PurchaseCreditsAction;

class CreditlessGateway implements BillingContract
{
    public function ensureCustomerExists(Company|User $billable): void {}

    public function isSubscribed(Company|User $billable, string $planSlug): bool
    {
        return false;
    }

    public function createCheckout(Company|User $billable, CheckoutData $data): string
    {
        return 'https://checkout.test';
    }

    public function checkoutOpensInNewTab(): bool
    {
        return false;
    }

    public function getBillingPortalUrl(Company|User $billable, string $returnUrl, array $options = []): string
    {
        return 'https://billing.test';
    }

    public function hasActiveSubscription(Company|User $billable): bool
    {
        return false;
    }
}

class CreditSellingGateway extends CreditlessGateway implements SupportsCreditPurchase
{
    public function purchaseCredits(Company|User $billable, Company $company, int $quantity, string $successUrl, string $cancelUrl): string
    {
        return 'https://checkout.test/credits';
    }
}

function mockSellingDriver(BillingContract $driver): void
{
    test()->instance(BillingManager::class, Mockery::mock(BillingManager::class, function ($mock) use ($driver): void {
        $mock->shouldReceive('getDriver')->andReturn($driver);
    }));
}

it('offers the button when the selling gateway can charge for credits', function (): void {
    mockSellingDriver(new CreditSellingGateway);

    expect(PurchaseCreditsAction::make()->isVisible())->toBeTrue();
});

it('hides the button when the selling gateway cannot charge for credits', function (): void {
    mockSellingDriver(new CreditlessGateway);

    expect(PurchaseCreditsAction::make()->isVisible())->toBeFalse();
});
