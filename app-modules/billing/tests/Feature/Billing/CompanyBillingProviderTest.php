<?php

use TresPontosTech\Billing\Core\Filament\CompanyBillingProvider;
use TresPontosTech\Billing\Core\Http\Middleware\RedirectCompanyIfNotSubscribed;

it('getSubscribedMiddleware returns the RedirectCompanyIfNotSubscribed class name', function (): void {
    $provider = new CompanyBillingProvider;

    expect($provider->getSubscribedMiddleware())->toBe(RedirectCompanyIfNotSubscribed::class);
});

it('getRouteAction returns a Closure', function (): void {
    $provider = new CompanyBillingProvider;

    expect($provider->getRouteAction())->toBeInstanceOf(Closure::class);
});
