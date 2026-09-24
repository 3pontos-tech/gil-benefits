<?php

use TresPontosTech\Billing\Core\Filament\UserBillingProvider;
use TresPontosTech\Billing\Core\Http\Middleware\RedirectUserIfNotSubscribed;

it('getSubscribedMiddleware returns the RedirectUserIfNotSubscribed class name', function (): void {
    $provider = new UserBillingProvider;

    expect($provider->getSubscribedMiddleware())->toBe(RedirectUserIfNotSubscribed::class);
});

it('getRouteAction returns a Closure', function (): void {
    $provider = new UserBillingProvider;

    expect($provider->getRouteAction())->toBeInstanceOf(Closure::class);
});
