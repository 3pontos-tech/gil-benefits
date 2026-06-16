<?php

use TresPontosTech\Billing\Stripe\Subscription\Company\CompanyBillingProvider;
use TresPontosTech\Billing\Stripe\Subscription\Company\RedirectCompanyIfNotSubscribed;

it('getSubscribedMiddleware returns the RedirectCompanyIfNotSubscribed class name', function (): void {
    $provider = new CompanyBillingProvider;

    expect($provider->getSubscribedMiddleware())->toBe(RedirectCompanyIfNotSubscribed::class);
});

it('getRouteAction returns a Closure', function (): void {
    $provider = new CompanyBillingProvider;

    expect($provider->getRouteAction())->toBeInstanceOf(Closure::class);
});
