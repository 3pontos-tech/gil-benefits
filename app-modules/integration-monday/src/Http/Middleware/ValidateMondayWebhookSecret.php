<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ValidateMondayWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('monday.webhook_secret');

        abort_if(blank($secret) || ! hash_equals((string) $secret, (string) $request->query('token')), 401);

        return $next($request);
    }
}
