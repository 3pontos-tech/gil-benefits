<?php

namespace TresPontosTech\Billing\Stripe\Subscription\Company;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use TresPontosTech\Billing\Core\Contracts\BillingContract;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;
use TresPontosTech\Company\Models\Company;

class RedirectCompanyIfNotSubscribed
{
    public function handle(Request $request, Closure $next, string ...$plans)
    {
        /** @var Company|Filament $tenant */
        $tenant = Filament::getTenant();

        if ($tenant->slug === 'flamma-company') {
            return $next($request);
        }

        if ($tenant->hasActivePlan()) {
            return $next($request);
        }

        $billing = resolve(BillingContract::class);
        $billing->ensureCustomerExists($tenant);

        $plans = resolve(PlanRepository::class)->all();

        foreach ($plans as $plan) {
            if ($billing->isSubscribed($tenant, $plan->slug)) {
                return $next($request);
            }
        }

        $route = 'filament.company.pages.available-subscriptions';

        if (request()->routeIs($route)) {
            return $next($request);
        }

        return to_route($route, ['tenant' => $tenant->slug]);

    }
}
