<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guarda num cookie de 30 dias, até o cadastro, de onde a pessoa veio. O `flare_id` do convite
 * vale pelo último toque (o convite mais recente ganha); as UTMs valem pelo primeiro toque e não
 * são sobrescritas pelas visitas seguintes.
 */
class StoreSignupAttribution
{
    public const string COOKIE = 'signup_attribution';

    private const int DAYS = 30;

    private const array UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'];

    public function handle(Request $request, Closure $next): Response
    {
        $stored = self::current($request);
        $attribution = $stored;

        $flareId = $request->query('flare_id');

        if (is_string($flareId) && str($flareId)->isUuid()) {
            $attribution['flare_id'] = $flareId;
        }

        $utms = array_filter(
            Arr::only($request->query(), self::UTM_KEYS),
            fn (mixed $value): bool => is_string($value) && filled($value),
        );

        if ($utms !== [] && ! isset($attribution['utms'])) {
            $attribution['utms'] = array_map(fn (string $value): string => str($value)->limit(200, '')->toString(), $utms);
        }

        if ($attribution !== $stored) {
            $request->attributes->set(self::COOKIE, $attribution);
            Cookie::queue(self::COOKIE, (string) json_encode($attribution), 60 * 24 * self::DAYS);
        }

        return $next($request);
    }

    /**
     * @return array{flare_id?: string, utms?: array<string, string>}
     */
    public static function current(Request $request): array
    {
        $cookie = $request->cookie(self::COOKIE);
        $raw = $request->attributes->get(self::COOKIE) ?? (is_string($cookie) ? json_decode($cookie, true) : null);

        if (! is_array($raw)) {
            return [];
        }

        $attribution = [];

        if (is_string($raw['flare_id'] ?? null) && str($raw['flare_id'])->isUuid()) {
            $attribution['flare_id'] = $raw['flare_id'];
        }

        $utms = array_filter(
            Arr::only(is_array($raw['utms'] ?? null) ? $raw['utms'] : [], self::UTM_KEYS),
            fn (mixed $value): bool => is_string($value) && filled($value),
        );

        if ($utms !== []) {
            $attribution['utms'] = $utms;
        }

        return $attribution;
    }

    public static function forget(): void
    {
        Cookie::queue(Cookie::forget(self::COOKIE));
    }
}
