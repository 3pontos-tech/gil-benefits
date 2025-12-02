<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class AuthorizationAuditMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        $user = $request->user();

        // If user is not authenticated, let the request continue
        if (!$user) {
            return $next($request);
        }

        // Set up gate event listeners for audit logging
        $this->setupGateEventListeners($user, $request);

        // Log request start
        $this->logRequestStart($user, $request);

        $response = $next($request);

        // Log request completion
        $this->logRequestCompletion($user, $request, $response);

        return $response;
    }

    /**
     * Set up event listeners for gate checks.
     */
    protected function setupGateEventListeners(\App\Models\Users\User $user, Request $request): void
    {
        // Listen for gate checks
        Gate::after(function ($user, $ability, $result, $arguments) use ($request) {
            app(\App\Services\AuthorizationAuditService::class)->logGateCheck(
                $user,
                $ability,
                $arguments,
                $result,
                [
                    'route_name' => $request->route()?->getName(),
                    'route_uri' => $request->route()?->uri(),
                    'request_method' => $request->method(),
                    'request_id' => $request->header('X-Request-ID') ?? \Illuminate\Support\Str::uuid(),
                ]
            );
        });
    }

    /**
     * Log the start of a request for audit purposes.
     */
    protected function logRequestStart(\App\Models\Users\User $user, Request $request): void
    {
        $requestId = $request->header('X-Request-ID') ?? \Illuminate\Support\Str::uuid();
        $request->headers->set('X-Request-ID', $requestId);

        app(\App\Services\AuthorizationAuditService::class)->logAuthorizationDecision(
            $user,
            'request_start',
            null,
            true,
            'User initiated request',
            [
                'request_id' => $requestId,
                'route_name' => $request->route()?->getName(),
                'route_uri' => $request->route()?->uri(),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'request_parameters' => $this->sanitizeRequestParameters($request),
                'user_agent' => $request->userAgent(),
                'referer' => $request->header('referer'),
                'is_ajax' => $request->ajax(),
                'is_json' => $request->expectsJson(),
                'content_type' => $request->header('content-type'),
                'type' => 'request_audit',
            ]
        );
    }

    /**
     * Log the completion of a request for audit purposes.
     */
    protected function logRequestCompletion(\App\Models\Users\User $user, Request $request, BaseResponse $response): void
    {
        $requestId = $request->header('X-Request-ID');
        $statusCode = $response->getStatusCode();
        $isSuccessful = $statusCode >= 200 && $statusCode < 300;
        $isRedirect = $statusCode >= 300 && $statusCode < 400;
        $isError = $statusCode >= 400;

        app(\App\Services\AuthorizationAuditService::class)->logAuthorizationDecision(
            $user,
            'request_completion',
            null,
            $isSuccessful || $isRedirect,
            $this->getResponseDescription($statusCode),
            [
                'request_id' => $requestId,
                'route_name' => $request->route()?->getName(),
                'route_uri' => $request->route()?->uri(),
                'request_method' => $request->method(),
                'response_status_code' => $statusCode,
                'response_size' => strlen($response->getContent()),
                'is_successful' => $isSuccessful,
                'is_redirect' => $isRedirect,
                'is_error' => $isError,
                'execution_time_ms' => $this->getExecutionTime($request),
                'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'type' => 'request_audit',
            ]
        );

        // Log security events for error responses
        if ($isError) {
            $this->logErrorResponse($user, $request, $response);
        }
    }

    /**
     * Sanitize request parameters for logging (remove sensitive data).
     */
    protected function sanitizeRequestParameters(Request $request): array
    {
        $parameters = $request->all();
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'card_number',
            'cvv',
            'ssn',
            'social_security',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($parameters[$field])) {
                $parameters[$field] = '[REDACTED]';
            }
        }

        // Also check nested arrays
        array_walk_recursive($parameters, function (&$value, $key) use ($sensitiveFields) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $value = '[REDACTED]';
            }
        });

        return $parameters;
    }

    /**
     * Get a human-readable description for the response status code.
     */
    protected function getResponseDescription(int $statusCode): string
    {
        return match ($statusCode) {
            200 => 'Request completed successfully',
            201 => 'Resource created successfully',
            204 => 'Request completed with no content',
            301, 302, 303, 307, 308 => 'Request redirected',
            400 => 'Bad request - invalid input',
            401 => 'Unauthorized - authentication required',
            403 => 'Forbidden - insufficient permissions',
            404 => 'Resource not found',
            405 => 'Method not allowed',
            422 => 'Validation failed',
            429 => 'Rate limit exceeded',
            500 => 'Internal server error',
            503 => 'Service unavailable',
            default => "HTTP {$statusCode} response",
        };
    }

    /**
     * Get the execution time for the request.
     */
    protected function getExecutionTime(Request $request): float
    {
        if (defined('LARAVEL_START')) {
            return round((microtime(true) - LARAVEL_START) * 1000, 2);
        }

        return 0.0;
    }

    /**
     * Log error responses for security monitoring.
     */
    protected function logErrorResponse(\App\Models\Users\User $user, Request $request, BaseResponse $response): void
    {
        $statusCode = $response->getStatusCode();
        $requestId = $request->header('X-Request-ID');

        $eventType = match ($statusCode) {
            401 => 'authentication_failure',
            403 => 'authorization_failure',
            404 => 'resource_not_found',
            405 => 'method_not_allowed',
            422 => 'validation_failure',
            429 => 'rate_limit_exceeded',
            500, 503 => 'server_error',
            default => 'http_error',
        };

        $severity = match ($statusCode) {
            401, 403 => 'medium',
            429 => 'high',
            500, 503 => 'critical',
            default => 'low',
        };

        app(\App\Services\SecurityLoggingService::class)->logSecurityEvent(
            $eventType,
            "HTTP {$statusCode} error response",
            [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'request_id' => $requestId,
                'status_code' => $statusCode,
                'route_name' => $request->route()?->getName(),
                'route_uri' => $request->route()?->uri(),
                'request_method' => $request->method(),
                'request_url' => $request->fullUrl(),
                'severity' => $severity,
                'is_partner_collaborator' => $user->isPartnerCollaborator(),
                'user_roles' => $user->companies->pluck('pivot.role')->filter()->unique()->values()->toArray(),
            ]
        );

        // For critical errors, create incident reports
        if ($severity === 'critical') {
            app(\App\Services\SecurityLoggingService::class)->createIncidentReport(
                'critical_application_error',
                [
                    'severity' => 'critical',
                    'description' => "Critical application error (HTTP {$statusCode}) encountered",
                    'user_id' => $user->id,
                    'request_id' => $requestId,
                    'status_code' => $statusCode,
                    'affected_resources' => [$request->route()?->getName() ?? 'unknown_route'],
                    'mitigation_steps' => [
                        'Investigate server logs for root cause',
                        'Check application health and dependencies',
                        'Monitor for similar errors',
                    ],
                ]
            );
        }
    }
}