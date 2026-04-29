<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ValidateBarteWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('services.barte.webhook_secret');

        abort_if(blank($secret) || ! hash_equals($secret, (string) $request->query('token')), 401);

        return $next($request);
    }
}
