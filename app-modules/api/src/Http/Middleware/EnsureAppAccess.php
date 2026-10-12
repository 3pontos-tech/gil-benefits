<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Middleware;

use App\Models\Users\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Api\Actions\V1\Employee\ResolveEmployeeCompanyAction;

/**
 * Barra quem tem token válido mas não é público do app, e fixa a empresa da requisição.
 *
 * Primeiro o público (User::canUseApp(): vínculo ativo, sem ser consultor); depois o benefício
 * pago, pela mesma régua do painel (ResolveEmployeeCompanyAction). O plano pode vencer depois do
 * login, por isso a conferência é a cada requisição; a resposta 403 traz o motivo para o app
 * mostrar antes de voltar ao login.
 */
class EnsureAppAccess
{
    public function __construct(private readonly ResolveEmployeeCompanyAction $resolveCompany) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->canUseApp(), Response::HTTP_FORBIDDEN, __('api::auth.no_access'));

        $access = $this->resolveCompany->handle($user);

        abort_unless($access->isGranted(), Response::HTTP_FORBIDDEN, $access->denialMessage());

        return $next($request);
    }
}
