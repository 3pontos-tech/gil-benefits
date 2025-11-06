<?php

namespace TresPontosTech\Billing\Stripe\Subscription\Company;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use TresPontosTech\Billing\Core\PlanRepository;
use TresPontosTech\Company\Models\Company;

class RedirectCompanyIfNotSubscribed
{
    public function handle(Request $request, Closure $next, string ...$plans)
    {
        /** @var Company|Filament $tenant */
        $tenant = Filament::getTenant();

        if ($tenant->hasStripeId() === false) {
            $tenant->createAsStripeCustomer([
                'metadata' => [
                    'model_type' => Company::class,
                ],
            ]);
        }

        $plans = resolve(PlanRepository::class)->all();
        foreach ($plans as $plan) {
            if ($tenant->subscribed($plan->type)) {
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
