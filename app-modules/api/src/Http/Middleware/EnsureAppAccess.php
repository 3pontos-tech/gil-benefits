<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Middleware;

use App\Models\Users\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Api\Actions\Employee\Auth\ResolveAppAccessAction;

/**
 * A conferência é a cada requisição porque o plano pode vencer, ou a empresa desativar a pessoa,
 * depois do login; o 403 traz o motivo para o app mostrar antes de voltar ao login. A empresa
 * liberada vira o tenant da requisição, sem disparar eventos: cota, crédito e agendamento a leem
 * por `ResolveQuotaAllowance::companyIdFor()`, como no painel.
 */
class EnsureAppAccess
{
    public function __construct(private readonly ResolveAppAccessAction $resolveAppAccess) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, Response::HTTP_FORBIDDEN, __('api::auth.no_access'));

        $access = $this->resolveAppAccess->handle($user);

        abort_unless($access->isGranted(), Response::HTTP_FORBIDDEN, $access->message());

        Filament::setTenant($access->company, isQuiet: true);

        return $next($request);
    }
}
