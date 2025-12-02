<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register custom middleware
        $middleware->alias([
            'security.headers' => \App\Http\Middleware\SecurityHeadersMiddleware::class,
            'security.logging' => \App\Http\Middleware\SecurityLoggingMiddleware::class,
            'tenant.isolation' => \App\Http\Middleware\TenantIsolationMiddleware::class,
            'panel.access' => \App\Http\Middleware\AuthorizePanelAccess::class,
            'role.access' => \App\Http\Middleware\RoleBasedAccessControlMiddleware::class,
            'auth.audit' => \App\Http\Middleware\AuthorizationAuditMiddleware::class,
            'enhanced.auth' => \App\Http\Middleware\EnhancedAuthorizationMiddleware::class,
            'performance.monitoring' => \App\Http\Middleware\PerformanceMonitoringMiddleware::class,
        ]);

        // Apply security middleware globally
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\SecurityLoggingMiddleware::class,
            \App\Http\Middleware\AuthorizationAuditMiddleware::class,
            \App\Http\Middleware\TenantIsolationMiddleware::class,
            \App\Http\Middleware\PerformanceMonitoringMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Register custom exception handling
        $exceptions->render(function (\App\Exceptions\BaseException $e, \Illuminate\Http\Request $request) {
            // Report the exception
            $e->report();

            // Return custom response for API requests
            if ($request->expectsJson()) {
                return $e->render($request);
            }

            // Let Laravel handle non-API requests normally
            return null;
        });

        // Handle validation exceptions for API requests
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return \App\Http\Responses\ApiErrorResponse::validationError(
                    'Validation failed',
                    $e->errors(),
                    'VALIDATION_FAILED'
                );
            }

            return null;
        });

        // Handle authentication exceptions for API requests
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return \App\Http\Responses\ApiErrorResponse::unauthorized(
                    'Authentication required',
                    'AUTHENTICATION_REQUIRED'
                );
            }

            return null;
        });

        // Handle authorization exceptions for API requests
        $exceptions->render(function (\Illuminate\Auth\Access\AuthorizationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return \App\Http\Responses\ApiErrorResponse::forbidden(
                    'Access denied',
                    'ACCESS_DENIED'
                );
            }

            return null;
        });

        // Handle model not found exceptions for API requests
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return \App\Http\Responses\ApiErrorResponse::notFound(
                    'Resource not found',
                    'RESOURCE_NOT_FOUND'
                );
            }

            return null;
        });

        // Handle database exceptions
        $exceptions->render(function (\Illuminate\Database\QueryException $e, \Illuminate\Http\Request $request) {
            // Log the database error
            app(\App\Services\Monitoring\SystemMonitor::class)->recordError(
                'database_error',
                $e->getMessage(),
                [
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                ],
                'error'
            );

            if ($request->expectsJson()) {
                return \App\Http\Responses\ApiErrorResponse::internalServerError(
                    'Database error occurred',
                    'DATABASE_ERROR',
                    [],
                    $e
                );
            }

            return null;
        });

        // Handle general exceptions for API requests
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            // Record system error
            app(\App\Services\Monitoring\SystemMonitor::class)->recordError(
                'system_error',
                $e->getMessage(),
                [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                'error'
            );

            if ($request->expectsJson()) {
                return \App\Http\Responses\ApiErrorResponse::fromException($e);
            }

            return null;
        });
    })->create();
