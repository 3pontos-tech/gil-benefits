<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Stripe\Subscription\Company;

use Closure;
use Filament\Billing\Providers\Contracts\BillingProvider;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
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

            $driver = $providerEnum instanceof BillingProviderEnum
                ? $billing->getDriver(BillingProviderEnum::from($providerEnum->value))
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
