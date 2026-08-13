<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve o tenant padrão nas rotas autenticadas que não carregam `{tenant}`.
 *
 * O `IdentifyTenant` do Filament só age quando a rota tem o parâmetro `{tenant}`,
 * então numa rota como `app/profile` nenhum tenant fica em escopo. Isso não
 * incomodava enquanto a página de perfil usava o layout simples, sem sidebar; ao
 * passar a renderizar a navegação do painel, cada item que aponta para uma rota
 * tenant-scoped (`app/{tenant}/my-credits`, por exemplo) estourava
 * `UrlGenerationException` por falta do parâmetro.
 *
 * A escolha do tenant não é arbitrária: usa `getUserDefaultTenant()`, a mesma
 * resolução que o `RedirectToTenantController` do Filament aplica quando o
 * usuário abre `/app` sem tenant na URL.
 */
class IdentifyDefaultTenantOffTenantRoutes
{
    public function handle(Request $request, Closure $next): Response
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        // Rotas com {tenant} continuam por conta do IdentifyTenant, que também
        // valida o acesso do usuário àquela empresa.
        if (! $panel->hasTenancy() || $request->route()?->hasParameter('tenant') === true) {
            return $next($request);
        }

        if (Filament::getTenant() instanceof Model) {
            return $next($request);
        }

        $user = $panel->auth()->user();

        if ($user === null) {
            return $next($request);
        }

        $tenant = Filament::getUserDefaultTenant($user);

        if ($tenant !== null) {
            Filament::setTenant($tenant);
        }

        return $next($request);
    }
}
