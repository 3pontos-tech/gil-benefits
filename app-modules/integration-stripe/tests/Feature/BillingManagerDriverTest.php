<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\IntegrationStripe\StripeAdapter;

it('resolves the stripe driver registered by this module', function (): void {
    expect(resolve(BillingManager::class)->getDriver(BillingProviderEnum::Stripe))
        ->toBeInstanceOf(StripeAdapter::class);
});

it('sells new subscriptions through stripe and only honours stripe plans', function (): void {
    expect(BillingProviderEnum::checkoutCases())->toBe([BillingProviderEnum::Stripe])
        ->and(BillingProviderEnum::activeCases())->toBe([BillingProviderEnum::Stripe])
        ->and(resolve(BillingManager::class)->getDriver())->toBeInstanceOf(StripeAdapter::class);
});
