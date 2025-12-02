<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Monitoring\ErrorRateMonitoringService;
use App\Services\Monitoring\PerformanceMetricsCollector;
use App\Services\Monitoring\UserActivityTracker;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoringMiddleware
{
    public function __construct(
        private readonly PerformanceMetricsCollector $metricsCollector,
        private readonly UserActivityTracker $activityTracker,
        private readonly ErrorRateMonitoringService $errorRateService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        // Process the request
        $response = $next($request);

        // Calculate performance metrics
        $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = memory_get_usage(true) - $startMemory;

        // Get route information
        $route = $request->route();
        $routeName = $route ? $route->getName() : null;
        $routeUri = $route ? $route->uri() : $request->getPathInfo();

        // Record response time
        $this->metricsCollector->recordResponseTime(
            $responseTime,
            $routeName ?: $routeUri,
            $response->getStatusCode()
        );

        // Record request for error rate monitoring
        $user = Auth::user();
        $this->errorRateService->recordRequest(
            $routeUri,
            $response->getStatusCode(),
            $responseTime,
            $user?->id
        );

        // Track user activity
        if ($user) {
            $this->activityTracker->trackPageView($user, $routeUri, $request);
        }

        // Track API usage if this is an API request
        if ($request->is('api/*')) {
            $this->activityTracker->trackFeatureUsage($user, 'api_request', [
                'endpoint' => $routeUri,
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'response_time_ms' => $responseTime,
            ]);
        }

        // Add performance headers for debugging (only in non-production)
        if (!app()->isProduction()) {
            $response->headers->set('X-Response-Time', round($responseTime, 2) . 'ms');
            $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsed));
            $response->headers->set('X-Peak-Memory', $this->formatBytes(memory_get_peak_usage(true)));
        }

        return $response;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
