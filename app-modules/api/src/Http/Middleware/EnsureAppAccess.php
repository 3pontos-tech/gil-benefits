<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Middleware;

use App\Models\Users\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Barra quem tem token válido mas não é público do app.
 *
 * @see User::canUseApp()
 */
class EnsureAppAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->canUseApp(), Response::HTTP_FORBIDDEN, __('api::auth.no_access'));

        return $next($request);
    }
}
