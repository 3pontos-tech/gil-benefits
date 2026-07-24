<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\RedirectsToProfileHome;
use Closure;
use Filament\Facades\Filament;
use Filament\Http\Middleware\IdentifyTenant;
use Filament\Models\Contracts\HasTenants;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Drop-in replacement for Filament's IdentifyTenant middleware.
 *
 * Mirrors the original tenant identification exactly, except that an
 * authenticated user who tries to open a company (tenant) they do not belong to
 * is redirected to their own profile home instead of receiving a bare 404.
 * A genuinely missing tenant still resolves to a 404 through `getTenant()`.
 */
class IdentifyTenantOrRedirectHome extends IdentifyTenant
{
    use RedirectsToProfileHome;

    public function handle(Request $request, Closure $next): mixed
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if (! $panel->hasTenancy()) {
            return $next($request);
        }

        if (! $request->route()->hasParameter('tenant')) {
            return $next($request);
        }

        $user = $panel->auth()->user();

        abort_unless($user instanceof HasTenants, Response::HTTP_NOT_FOUND);

        $tenantKey = $request->route()->parameter('tenant');

        abort_unless(is_string($tenantKey), Response::HTTP_NOT_FOUND);

        $tenant = $panel->getTenant($tenantKey);

        if (! $user->canAccessTenant($tenant)) {
            // Only change from Filament core: an authenticated user is redirected
            // to their profile home instead of getting a 404 for a company they
            // are not part of.
            return $this->profileHomeResponse($request, $user);
        }

        Filament::setTenant($tenant);

        return $next($request);
    }
}
