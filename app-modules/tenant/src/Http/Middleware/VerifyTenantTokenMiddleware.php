<?php

namespace TresPontosTech\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Company\Models\Company;

class VerifyTenantTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request):Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->hasHeader(config('tenant.header'))) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $request->header(config('tenant.header'));
        Company::query()->where('integration_access_key', $token)->firstOrFail();

        return $next($request);
    }
}
