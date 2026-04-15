<?php

namespace TresPontosTech\App\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAnamneseNotCompleted
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $anamneseRoute = 'filament.app.pages.anamnese';
        $subscriptionRoute = 'filament.app.pages.available-subscriptions';

        if ($request->routeIs($anamneseRoute, $subscriptionRoute)) {
            return $next($request);
        }

        $user = auth()->user();
        $tenant = filament()->getTenant();

        if ($user === null || $user->anamnese !== null) {
            return $next($request);
        }

        if (! $tenant instanceof Model) {
            return $next($request);
        }

        $hasSubscription = $tenant->hasActivePlan() || $user->activeSubscription()->exists();

        if (! $hasSubscription) {
            return $next($request);
        }

        return to_route($anamneseRoute, ['tenant' => $tenant->slug]);
    }
}
