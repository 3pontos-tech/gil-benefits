<?php

namespace TresPontosTech\Billing\Stripe\Subscription\User;

use Closure;
use Filament\Billing\Providers\Contracts\BillingProvider;
use Filament\Pages\Dashboard;

class UserBillingProvider implements BillingProvider
{
    public function getRouteAction(): string|Closure|array
    {
        return static function () {
            $tenant = auth()->user();

            if ($tenant->hasStripeId() === false) {
                $tenant->createAsStripeCustomer([
                    'metadata' => [
                        'model_type' => \App\Models\Users\User::class,
                    ],
                ]);
            }

            return $tenant->redirectToBillingPortal(returnUrl: Dashboard::getUrl(), options: [
                'configuration' => config('cashier.portals.user'),
            ]);
        };
    }

    public function getSubscribedMiddleware(): string
    {
        return RedirectUserIfNotSubscribed::class;
    }
}
