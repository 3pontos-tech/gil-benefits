<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Http\Middleware;

use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates an inbound ChatX request through four gates: source IP, required
 * headers, timestamp freshness, and an HMAC-SHA256 signature over the raw body.
 *
 * Every failure answers a bare 401 with no hint of which gate rejected it. That is
 * the point: an attacker probing the endpoint learns nothing about whether their
 * address is listed, their clock is off, or their key is wrong.
 *
 * This is a stronger contract than the other webhooks in this codebase — Barte and
 * Monday only compare a `?token=` query parameter, which proves the caller knew a
 * shared string but says nothing about the body. Here the signature covers the
 * payload, so it also proves nobody rewrote it in transit.
 */
final class ValidateChatxRequest
{
    public const TIMESTAMP_HEADER = 'X-Timestamp';

    public const SIGNATURE_HEADER = 'X-Signature';

    /**
     * ISO 8601 with an explicit zone — `...Z` or `...±HH:MM`. A timestamp without a
     * zone would be read in the app's timezone, which silently shifts the freshness
     * window by hours and turns a valid request into a rejected one (or the reverse).
     */
    private const ISO_8601_WITH_ZONE = '/^\d{4}-\d{2}-\d{2}[Tt]\d{2}:\d{2}:\d{2}(\.\d+)?([Zz]|[+-]\d{2}:\d{2})$/';

    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('chatx.webhook_secret');
        $timestamp = (string) $request->header(self::TIMESTAMP_HEADER);
        $signature = (string) $request->header(self::SIGNATURE_HEADER);

        abort_if(! $this->fromAllowedIp($request), 401);
        abort_if(blank($secret) || blank($timestamp) || blank($signature), 401);
        abort_if(! $this->isFresh($timestamp), 401);
        abort_if(! $this->isSigned($request, $timestamp, $signature, $secret), 401);

        return $next($request);
    }

    /**
     * An empty allowlist disables the check — see config/chatx.php for why.
     *
     * Behind a load balancer or CDN this reads the peer address unless trusted
     * proxies are configured, so `$request->ip()` would be the proxy and no real
     * ChatX address would ever match.
     */
    private function fromAllowedIp(Request $request): bool
    {
        /** @var list<string> $allowed */
        $allowed = config('chatx.allowed_ips', []);

        if ($allowed === []) {
            return true;
        }

        $ip = $request->ip();

        return $ip !== null && IpUtils::checkIp($ip, $allowed);
    }

    private function isFresh(string $timestamp): bool
    {
        if (preg_match(self::ISO_8601_WITH_ZONE, $timestamp) !== 1) {
            return false;
        }

        try {
            $sentAt = CarbonImmutable::parse($timestamp);
        } catch (InvalidFormatException) {
            return false;
        }

        $tolerance = (int) config('chatx.timestamp_tolerance', 600);

        // Absolute difference: clocks drift both ways, and a request stamped in the
        // future is just as suspect as a stale one.
        return $sentAt->diffInSeconds(CarbonImmutable::now(), absolute: true) <= $tolerance;
    }

    /**
     * The signed message is the timestamp concatenated with the body exactly as it
     * arrived. `getContent()` is the raw bytes; re-encoding `$request->all()` would
     * reorder keys and normalise whitespace, and the digest would never match.
     */
    private function isSigned(Request $request, string $timestamp, string $signature, string $secret): bool
    {
        $expected = hash_hmac('sha256', $timestamp . $request->getContent(), $secret);

        return hash_equals($expected, $signature);
    }
}
