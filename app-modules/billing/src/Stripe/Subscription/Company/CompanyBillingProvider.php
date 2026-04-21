<?php

namespace TresPontosTech\Billing\Stripe\Subscription\Company;

use Closure;
use Filament\Billing\Providers\Contracts\BillingProvider;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use TresPontosTech\Billing\BillingCustomer;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Company\Models\Company;

class CompanyBillingProvider implements BillingProvider
{
    public function getRouteAction(): string|Closure|array
    {
        return static function (): Redirector|RedirectResponse {
            /** @var Company $tenant */
            $tenant = Filament::getTenant();

            $providerEnum = BillingCustomer::getActiveProvider($tenant);

            $billing = resolve(BillingManager::class);

            $driver = $providerEnum
                ? $billing->driver($providerEnum->value)
                : $billing->getDefaultDriver();

            $driver->ensureCustomerExists($tenant);

            $url = $driver->getBillingPortalUrl(
                billable: $tenant,
                returnUrl: Dashboard::getUrl(),
            );

            return redirect($url);
        };
    }

    public function getSubscribedMiddleware(): string
    {
        return RedirectCompanyIfNotSubscribed::class;
    }
}
