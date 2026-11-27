<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\IntegrationStripe\StripeAdapter;

it('resolves the stripe driver registered by this module', function (): void {
    expect(resolve(BillingManager::class)->getDriver(BillingProviderEnum::Stripe))
        ->toBeInstanceOf(StripeAdapter::class);
});
