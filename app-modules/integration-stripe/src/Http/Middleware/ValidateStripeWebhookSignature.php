<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifies the Stripe-Signature header against the raw body: re-encoding the parsed
 * payload would change it and never match.
 */
final class ValidateStripeWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('stripe.webhook_secret');
        $signature = (string) $request->header('Stripe-Signature');

        abort_if(blank($secret) || blank($signature), 401);

        try {
            Webhook::constructEvent($request->getContent(), $signature, $secret, (int) config('stripe.webhook_tolerance'));
        } catch (SignatureVerificationException|UnexpectedValueException) {
            abort(401);
        }

        return $next($request);
    }
}
