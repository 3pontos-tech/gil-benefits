<?php

namespace TresPontosTech\Billing\Stripe\Subscription\User;

use Closure;
use Filament\Billing\Providers\Contracts\BillingProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use TresPontosTech\App\Filament\Pages\UserDashboard;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;

class UserBillingProvider implements BillingProvider
{
    public function getRouteAction(): string|Closure|array
    {
        return static function (): RedirectResponse|Redirector {
            $user = auth()->user();

            $providerEnum = BillingCustomer::getActiveProvider($user);

            $billing = resolve(BillingManager::class);

            $driver = $providerEnum instanceof BillingProviderEnum
                ? $billing->getDriver(BillingProviderEnum::from($providerEnum->value))
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
