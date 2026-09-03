<?php

namespace TresPontosTech\Billing\Core\Filament;

use Closure;
use Filament\Billing\Providers\Contracts\BillingProvider;
use Illuminate\Http\RedirectResponse;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Http\Middleware\RedirectUserIfNotSubscribed;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\PanelApp\Filament\Pages\UserDashboard;

class UserBillingProvider implements BillingProvider
{
    public function getRouteAction(): string|Closure|array
    {
        return static function (): RedirectResponse {
            $user = auth()->user();

            $providerEnum = BillingCustomer::getActiveProvider($user);

            $billing = resolve(BillingManager::class);

            $driver = $providerEnum instanceof BillingProviderEnum
                ? $billing->getDriver(BillingProviderEnum::from($providerEnum->value))
                : $billing->getDriver();

            $driver->ensureCustomerExists($user);

            $url = $driver->getBillingPortalUrl(
                billable: $user,
                returnUrl: UserDashboard::getUrl(),
            );

            return redirect($url);
        };
    }

    public function getSubscribedMiddleware(): string
    {
        return RedirectUserIfNotSubscribed::class;
    }
}
