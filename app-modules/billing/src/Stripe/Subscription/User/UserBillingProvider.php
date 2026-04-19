<?php

namespace TresPontosTech\Billing\Stripe\Subscription\User;

use Closure;
use Filament\Billing\Providers\Contracts\BillingProvider;
use TresPontosTech\App\Filament\Pages\UserDashboard;
use TresPontosTech\Billing\Core\Contracts\BillingContract;

class UserBillingProvider implements BillingProvider
{
    public function getRouteAction(): string|Closure|array
    {
        return static function () {
            $user = auth()->user();

            $billing = resolve(BillingContract::class);
            $billing->ensureUserCustomerExists($user);

            $billing->ensureUserCustomerExists($user);

            $url = $billing->getBillingPortalUrl(
                billable: $user,
                returnUrl: UserDashboard::getUrl(),
                options: ['configuration' => config('cashier.portals.user')],
            );

            return redirect($url);
        };
    }

    public function getSubscribedMiddleware(): string
    {
        return RedirectUserIfNotSubscribed::class;
    }
}
