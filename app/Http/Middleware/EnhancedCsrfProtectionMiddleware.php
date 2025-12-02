<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnhancedCsrfProtectionMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip CSRF check for safe methods
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // Skip CSRF check for API routes (they should use Sanctum or similar)
        if ($request->is('api/*')) {
            return $next($request);
        }

        // Enhanced CSRF validation
        if (! $this->validateCsrfToken($request)) {
            $this->logCsrfFailure($request);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'CSRF token mismatch.',
                    'error' => 'invalid_csrf_token',
                ], 419);
            }

            return redirect()->back()
                ->withErrors(['csrf' => 'Security token mismatch. Please try again.'])
                ->withInput($request->except(['password', 'password_confirmation', '_token']));
        }

        // Regenerate CSRF token for additional security on sensitive operations
        if ($this->isSensitiveOperation($request)) {
            $request->session()->regenerateToken();
        }

        return $next($request);
    }

    /**
     * Validate CSRF token with enhanced checks
     */
    protected function validateCsrfToken(Request $request): bool
    {
        $token = $request->input('_token') ?: $request->header('X-CSRF-TOKEN');

        if (! $token) {
            $token = $request->header('X-XSRF-TOKEN');
        }

        if (! $token) {
            return false;
        }

        $sessionToken = $request->session()->token();

        if (! $sessionToken) {
            return false;
        }

        // Use hash_equals to prevent timing attacks
        return hash_equals($sessionToken, $token);
    }

    /**
     * Log CSRF validation failures for security monitoring
     */
    protected function logCsrfFailure(Request $request): void
    {
        Log::channel('security')->warning('CSRF token validation failed', [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'referer' => $request->header('referer'),
            'has_token' => $request->has('_token'),
            'has_header_token' => $request->hasHeader('X-CSRF-TOKEN'),
            'has_xsrf_token' => $request->hasHeader('X-XSRF-TOKEN'),
            'session_id' => $request->session()->getId(),
            'user_id' => $request->user()?->id,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check if the operation is sensitive and requires token regeneration
     */
    protected function isSensitiveOperation(Request $request): bool
    {
        $sensitiveOperations = [
            'password',
            'email',
            'delete',
            'billing',
            'payment',
            'admin',
        ];

        $path = strtolower($request->path());

        foreach ($sensitiveOperations as $operation) {
            if (str_contains($path, $operation)) {
                return true;
            }
        }

        return false;
    }
}
