<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validates the X-Webhook-Signature header: HMAC-SHA256 of the request body,
 * keyed with the endpoint secret (a 64-char hex string from the Virtu panel).
 *
 * This deliberately departs from the house pattern. ValidateBarteWebhookSecret
 * and ValidateMondayWebhookSecret both compare a `?token=` query param, which
 * only proves the sender knew a shared string. Here the signature covers the
 * payload, so it also proves the body was not altered — provided we hash the raw
 * body. Re-encoding $request->all() would change key order and whitespace and
 * silently never match.
 */
final class ValidateVirtuWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('virtu.webhook_secret');
        $received = (string) $request->header('X-Webhook-Signature');

        abort_if(blank($secret) || blank($received), 401);

        $expected = hash_hmac('sha256', $request->getContent(), (string) $secret);

        abort_if(! hash_equals($expected, $received), 401);

        return $next($request);
    }
}
