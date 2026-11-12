<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarda na sessão, até o cadastro, de onde a pessoa veio: o `flare_id` do convite do Flare
 * e as UTMs do primeiro toque, que não são sobrescritas pelas visitas seguintes.
 */
class StoreSignupAttribution
{
    public const string FLARE_ID = 'signup_attribution.flare_id';

    public const string UTMS = 'signup_attribution.utms';

    private const array UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    public function handle(Request $request, Closure $next): Response
    {
        $flareId = $request->query('flare_id');

        if (is_string($flareId) && Str::isUuid($flareId)) {
            $request->session()->put(self::FLARE_ID, $flareId);
        }

        $utms = array_filter(
            Arr::only($request->query(), self::UTM_KEYS),
            fn (mixed $value): bool => is_string($value) && filled($value),
        );

        if ($utms !== [] && ! $request->session()->has(self::UTMS)) {
            $request->session()->put(self::UTMS, array_map(fn (string $value): string => Str::limit($value, 200, ''), $utms));
        }

        return $next($request);
    }
}
