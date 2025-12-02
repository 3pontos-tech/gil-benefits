<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Logging\StructuredLogger;
use App\Services\Monitoring\SystemMonitor;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ErrorHandlingMiddleware
{
    public function __construct(
        private readonly StructuredLogger $logger,
        private readonly SystemMonitor $monitor
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        try {
            $response = $next($request);

            $duration = microtime(true) - $startTime;

            // Log API requests
            if ($request->is('api/*')) {
                $this->logger->logApiRequest(
                    $request,
                    $response->getStatusCode(),
                    $duration
                );
            }

            // Record performance metrics
            $this->monitor->recordPerformanceMetric(
                'response_time',
                $duration * 1000, // Convert to milliseconds
                [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'status' => $response->getStatusCode(),
                ]
            );

            return $response;

        } catch (\Throwable $e) {
            $duration = microtime(true) - $startTime;

            // Record the error
            $this->monitor->recordError(
                'request_error',
                $e->getMessage(),
                [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'duration_ms' => round($duration * 1000, 2),
                    'exception' => get_class($e),
                ],
                'error'
            );

            throw $e;
        }
    }
}
