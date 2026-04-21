<?php

namespace TresPontosTech\Billing\Stripe\Subscription\User;

use Closure;
use Filament\Billing\Providers\Contracts\BillingProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use TresPontosTech\App\Filament\Pages\UserDashboard;
use TresPontosTech\Billing\BillingCustomer;
use TresPontosTech\Billing\Core\BillingManager;

class UserBillingProvider implements BillingProvider
{
    public function getRouteAction(): string|Closure|array
    {
        return static function (): RedirectResponse|Redirector {
            $user = auth()->user();

            $providerEnum = BillingCustomer::getActiveProvider($user);

            $billing = resolve(BillingManager::class);

            $driver = $providerEnum
                ? $billing->driver($providerEnum->value)
                : $billing->getDefaultDriver();

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
