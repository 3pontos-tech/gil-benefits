<?php

use TresPontosTech\PanelApp\Filament\UserBillingProvider;
use TresPontosTech\PanelApp\Http\Middleware\RedirectUserIfNotSubscribed;

it('getSubscribedMiddleware returns the RedirectUserIfNotSubscribed class name', function (): void {
    $provider = new UserBillingProvider;

    expect($provider->getSubscribedMiddleware())->toBe(RedirectUserIfNotSubscribed::class);
});

it('getRouteAction returns a Closure', function (): void {
    $provider = new UserBillingProvider;

    expect($provider->getRouteAction())->toBeInstanceOf(Closure::class);
});
