<?php

use TresPontosTech\Billing\Stripe\Subscription\User\RedirectUserIfNotSubscribed;
use TresPontosTech\Billing\Stripe\Subscription\User\UserBillingProvider;

it('getSubscribedMiddleware returns the RedirectUserIfNotSubscribed class name', function (): void {
    $provider = new UserBillingProvider;

    expect($provider->getSubscribedMiddleware())->toBe(RedirectUserIfNotSubscribed::class);
});

it('getRouteAction returns a Closure', function (): void {
    $provider = new UserBillingProvider;

    expect($provider->getRouteAction())->toBeInstanceOf(Closure::class);
});
