<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\IntegrationBarte\BarteAdapter;
use TresPontosTech\IntegrationVirtu\VirtuAdapter;

it('shares one BillingManager instance across the container', function (): void {
    // Regression guard. Manager::extend() stores the creator on the instance, so
    // a non-shared binding would let this module register the driver on one
    // instance while every later resolve() got a fresh one without it — the
    // driver would silently go missing.
    expect(resolve(BillingManager::class))->toBe(resolve(BillingManager::class));
});

it('resolves the virtu driver registered by this module', function (): void {
    expect(resolve(BillingManager::class)->getDriver(BillingProviderEnum::Virtu))
        ->toBeInstanceOf(VirtuAdapter::class);
});

it('leaves the existing barte driver untouched', function (): void {
    expect(resolve(BillingManager::class)->getDriver(BillingProviderEnum::Barte))
        ->toBeInstanceOf(BarteAdapter::class);
});
