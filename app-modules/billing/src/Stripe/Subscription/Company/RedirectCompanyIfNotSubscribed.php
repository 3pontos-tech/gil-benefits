<?php

namespace TresPontosTech\Billing\Stripe\Subscription\Company;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TresPontosTech\Company\Models\Company;

class RedirectCompanyIfNotSubscribed
{
    public function handle(Request $request, Closure $next, string ...$plans)
    {
        /** @var Company|Filament $tenant */
        $tenant = Filament::getTenant();

        $route = 'filament.company.pages.available-subscriptions';
        if (request()->routeIs($route)) {
            return $next($request);
        }

        if (! $tenant->subscribed('company')) {
            return to_route($route, ['tenant' => $tenant->slug]);
        }

        return $next($request);
    }
}
