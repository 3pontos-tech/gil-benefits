<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Middleware;

use App\Models\Users\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
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

        if (! $access->isGranted()) {
            $this->endSession($request);

            abort(Response::HTTP_FORBIDDEN, $access->message());
        }

        Filament::setTenant($access->company, isQuiet: true);

        return $next($request);
    }

    /**
     * Sem acesso, a sessão do app acaba também no servidor: o app volta ao login e não consegue
     * mais sair por conta própria (o logout passa por aqui). Sem isso, o token de acesso e o de
     * push sobreviveriam até o prune, e os pushes voltariam ao celular se o acesso voltasse, com a
     * pessoa já fora da conta. O token de push sai junto, pela FK.
     */
    private function endSession(Request $request): void
    {
        PersonalAccessToken::findToken((string) $request->bearerToken())?->delete();
    }
}
