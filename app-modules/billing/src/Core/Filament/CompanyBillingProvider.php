<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Filament;

use Closure;
use Filament\Billing\Providers\Contracts\BillingProvider;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Http\RedirectResponse;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Http\Middleware\RedirectCompanyIfNotSubscribed;
use TresPontosTech\Billing\Core\Models\BillingCustomer;
use TresPontosTech\Billing\Core\Pages\BillingManagePage;
use TresPontosTech\Company\Models\Company;

class CompanyBillingProvider implements BillingProvider
{
    public function getRouteAction(): string|Closure|array
    {
        return static function (): RedirectResponse {
            /** @var Company $tenant */
            $tenant = Filament::getTenant();

            $providerEnum = BillingCustomer::getActiveProvider($tenant);

            $billing = resolve(BillingManager::class);

            $driver = $providerEnum instanceof BillingProviderEnum
                ? $billing->getDriver(BillingProviderEnum::from($providerEnum->value))
                : $billing->getDriver();

            $driver->ensureCustomerExists($tenant);

            $portal = $driver->billingPortal(
                billable: $tenant,
                returnUrl: Dashboard::getUrl(),
            );

            return redirect($portal->url ?? BillingManagePage::getUrl(tenant: $tenant));
        };
    }

    public function getSubscribedMiddleware(): string
    {
        return RedirectCompanyIfNotSubscribed::class;
    }
}
