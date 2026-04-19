<?php

namespace TresPontosTech\Billing\Stripe\Subscription\Company;

use Closure;
use Filament\Billing\Providers\Contracts\BillingProvider;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Company\Models\Company;

class CompanyBillingProvider implements BillingProvider
{
    public function getRouteAction(): string|Closure|array
    {
        return static function () {
            /** @var Company $tenant */
            $tenant = Filament::getTenant();

            $billing = resolve(BillingContract::class);
            $billing->ensureCustomerExists($tenant);

            $url = $billing->getBillingPortalUrl(
                billable: $tenant,
                returnUrl: Dashboard::getUrl(),
                options: ['configuration' => config('cashier.portals.company')],
            );

            return redirect($url);
        };
    }

    public function getSubscribedMiddleware(): string
    {
        return RedirectCompanyIfNotSubscribed::class;
    }
}
