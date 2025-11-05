<?php

namespace TresPontosTech\Billing\Stripe\Subscription\User;

use Closure;
use Filament\Billing\Providers\Contracts\BillingProvider;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use TresPontosTech\Company\Models\Company;

class UserBillingProvider implements BillingProvider
{
    public function getRouteAction(): string|Closure|array
    {
        return static function () {
            /** @var Company $tenant */
            $tenant = Filament::getTenant();

            if ($tenant->hasStripeId() === false) {
                $tenant->createAsStripeCustomer();
            }

            return $tenant->redirectToBillingPortal(returnUrl: Dashboard::getUrl());
        };
    }

    public function getSubscribedMiddleware(): string
    {
        return RedirectUserIfNotSubscribed::class;
    }
}
