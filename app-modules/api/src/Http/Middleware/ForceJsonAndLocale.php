<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * O app sempre fala JSON e exibe as mensagens como chegam (A3, A5).
 *
 * Forçar o `Accept` faz o Laravel responder 401/403/404/422 no formato JSON
 * padrão mesmo quando o cliente esquece o header; fixar o locale garante as
 * mensagens de validação em português, independente do `Accept-Language`.
 */
class ForceJsonAndLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');
        app()->setLocale('pt_BR');

        return $next($request);
    }
}
