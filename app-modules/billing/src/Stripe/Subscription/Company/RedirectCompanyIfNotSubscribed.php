<?php

namespace TresPontosTech\Billing\Stripe\Subscription\Company;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Billing\Core\BillingManager;
use TresPontosTech\Billing\Core\Enums\BillingProviderEnum;
use TresPontosTech\Billing\Core\Repositories\PlanRepository;
use TresPontosTech\Company\Models\Company;

class RedirectCompanyIfNotSubscribed
{
    public function __construct(
        private readonly BillingManager $billingManager,
    ) {}

    public function handle(Request $request, Closure $next, string ...$plans): Response
    {
        /** @var Company|Filament $tenant */
        $tenant = Filament::getTenant();

        if ($tenant->slug === 'flamma-company') {
            return $next($request);
        }

        if ($tenant->hasActivePlan()) {
            return $next($request);
        }

        $plans = resolve(PlanRepository::class)->all();

        collect(BillingProviderEnum::checkoutCases())
            ->each(fn (BillingProviderEnum $provider) => $this->billingManager->getDriver($provider)->ensureCustomerExists($tenant));

        $hasValidSubscription = collect(BillingProviderEnum::activeCases())
            ->contains(fn (BillingProviderEnum $provider): bool => array_any(
                $plans,
                fn ($plan): bool => $this->billingManager->getDriver($provider)->isSubscribed($tenant, $plan->slug)
            ));

        if ($hasValidSubscription) {
            return $next($request);
        }

        $route = 'filament.company.pages.available-subscriptions';

        if (request()->routeIs($route)) {
            return $next($request);
        }

        return to_route($route, ['tenant' => $tenant->slug]);

    }
}
