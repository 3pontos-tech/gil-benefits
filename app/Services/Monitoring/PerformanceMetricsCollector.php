<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Services\Logging\StructuredLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PerformanceMetricsCollector
{
    private const CACHE_PREFIX = 'performance_metrics:';
    private const METRICS_RETENTION_HOURS = 24;

    public function __construct(
        private readonly StructuredLogger $logger,
        private readonly SystemMonitor $systemMonitor
    ) {}

    /**
     * Collect comprehensive application performance metrics.
     */
    public function collectMetrics(): array
    {
        $startTime = microtime(true);

        $metrics = [
            'timestamp' => now()->toISOString(),
            'application' => $this->collectApplicationMetrics(),
            'database' => $this->collectDatabaseMetrics(),
            'memory' => $this->collectMemoryMetrics(),
            'cache' => $this->collectCacheMetrics(),
            'queue' => $this->collectQueueMetrics(),
            'response_times' => $this->collectResponseTimeMetrics(),
        ];

        $collectionTime = (microtime(true) - $startTime) * 1000;
        $metrics['collection_time_ms'] = round($collectionTime, 2);

        // Store metrics for trend analysis
        $this->storeMetrics($metrics);

        // Check for performance alerts
        $this->checkPerformanceAlerts($metrics);

        $this->logger->logPerformanceEvent('metrics_collected', $collectionTime / 1000, [
            'metrics_count' => count($metrics),
            'collection_time_ms' => $collectionTime,
        ]);

        return $metrics;
    }

    /**
     * Collect application-level performance metrics.
     */
    private function collectApplicationMetrics(): array
    {
        return [
            'environment' => app()->environment(),
            'debug_mode' => config('app.debug'),
            'uptime_seconds' => $this->getApplicationUptime(),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'opcache_enabled' => function_exists('opcache_get_status') && opcache_get_status() !== false,
            'opcache_stats' => $this->getOpcacheStats(),
        ];
    }

    /**
     * Collect database performance metrics.
     */
    private function collectDatabaseMetrics(): array
    {
        $startTime = microtime(true);

        try {
            // Test database connectivity and response time
            DB::select('SELECT 1');
            $connectionTime = (microtime(true) - $startTime) * 1000;

            // Get query statistics
            $queryStats = $this->getQueryStatistics();

            // Get database size information
            $databaseInfo = $this->getDatabaseInfo();

            return [
                'connection_time_ms' => round($connectionTime, 2),
                'connection_status' => 'connected',
                'query_statistics' => $queryStats,
                'database_info' => $databaseInfo,
                'slow_queries' => $this->getSlowQueries(),
            ];

        } catch (\Exception $e) {
            return [
                'connection_status' => 'failed',
                'error' => $e->getMessage(),
                'connection_time_ms' => null,
            ];
        }
    }

    /**
     * Collect memory usage metrics.
     */
    private function collectMemoryMetrics(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        return [
            'current_usage_bytes' => $memoryUsage,
            'current_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_usage_bytes' => $memoryPeak,
            'peak_usage_mb' => round($memoryPeak / 1024 / 1024, 2),
            'limit_bytes' => $memoryLimit,
            'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'usage_percentage' => round(($memoryUsage / $memoryLimit) * 100, 2),
            'peak_percentage' => round(($memoryPeak / $memoryLimit) * 100, 2),
        ];
    }

    /**
     * Collect cache performance metrics.
     */
    private function collectCacheMetrics(): array
    {
        $startTime = microtime(true);

        try {
            // Test cache performance
            $testKey = 'performance_test_' . uniqid();
            $testValue = 'test_value_' . time();

            Cache::put($testKey, $testValue, 60);
            $retrievedValue = Cache::get($testKey);
            Cache::forget($testKey);

            $cacheTime = (microtime(true) - $startTime) * 1000;

            return [
                'response_time_ms' => round($cacheTime, 2),
                'status' => $retrievedValue === $testValue ? 'healthy' : 'degraded',
                'driver' => config('cache.default'),
                'hit_rate' => $this->getCacheHitRate(),
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'response_time_ms' => null,
            ];
        }
    }

    /**
     * Collect queue performance metrics.
     */
    private function collectQueueMetrics(): array
    {
        try {
            return [
                'default_connection' => config('queue.default'),
                'status' => 'active',
                'pending_jobs' => $this->getPendingJobsCount(),
                'failed_jobs' => $this->getFailedJobsCount(),
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Collect response time metrics from recent requests.
     */
    private function collectResponseTimeMetrics(): array
    {
        $recentMetrics = Cache::get(self::CACHE_PREFIX . 'response_times', []);

        if (empty($recentMetrics)) {
            return [
                'average_ms' => null,
                'median_ms' => null,
                'p95_ms' => null,
                'p99_ms' => null,
                'sample_size' => 0,
            ];
        }

        $times = array_column($recentMetrics, 'response_time');
        sort($times);

        return [
            'average_ms' => round(array_sum($times) / count($times), 2),
            'median_ms' => $this->calculatePercentile($times, 50),
            'p95_ms' => $this->calculatePercentile($times, 95),
            'p99_ms' => $this->calculatePercentile($times, 99),
            'sample_size' => count($times),
            'min_ms' => min($times),
            'max_ms' => max($times),
        ];
    }

    /**
     * Record response time for a request.
     */
    public function recordResponseTime(float $responseTime, string $route = null, int $statusCode = 200): void
    {
        $metric = [
            'response_time' => $responseTime,
            'route' => $route,
            'status_code' => $statusCode,
            'timestamp' => now()->timestamp,
        ];

        // Get existing metrics
        $recentMetrics = Cache::get(self::CACHE_PREFIX . 'response_times', []);

        // Add new metric
        $recentMetrics[] = $metric;

        // Keep only last 1000 entries
        if (count($recentMetrics) > 1000) {
            $recentMetrics = array_slice($recentMetrics, -1000);
        }

        // Store updated metrics
        Cache::put(self::CACHE_PREFIX . 'response_times', $recentMetrics, 3600);

        // Record in system monitor for alerting
        $this->systemMonitor->recordPerformanceMetric('response_time', $responseTime, [
            'route' => $route,
            'status_code' => $statusCode,
        ]);
    }

    /**
     * Get slow queries from the last hour.
     */
    private function getSlowQueries(): array
    {
        // For SQLite, we can't easily get slow queries
        // In a production environment with MySQL/PostgreSQL, you would query the slow query log
        return [];
    }

    /**
     * Get query statistics.
     */
    private function getQueryStatistics(): array
    {
        // Get query count from Laravel's query log if enabled
        $queryLog = DB::getQueryLog();

        if (empty($queryLog)) {
            return [
                'total_queries' => 0,
                'average_time_ms' => 0,
                'slowest_query_ms' => 0,
                'note' => 'Query logging is disabled',
            ];
        }

        $totalTime = array_sum(array_column($queryLog, 'time'));
        $slowestQuery = max(array_column($queryLog, 'time'));

        return [
            'total_queries' => count($queryLog),
            'average_time_ms' => round($totalTime / count($queryLog), 2),
            'slowest_query_ms' => $slowestQuery,
            'total_time_ms' => $totalTime,
        ];
    }

    /**
     * Get database information.
     */
    private function getDatabaseInfo(): array
    {
        try {
            $connection = DB::connection();
            $databaseName = $connection->getDatabaseName();

            // For SQLite, get file size
            if ($connection->getDriverName() === 'sqlite') {
                $databasePath = database_path($databaseName);
                $size = file_exists($databasePath) ? filesize($databasePath) : 0;

                return [
                    'driver' => 'sqlite',
                    'database' => $databaseName,
                    'size_bytes' => $size,
                    'size_mb' => round($size / 1024 / 1024, 2),
                ];
            }

            return [
                'driver' => $connection->getDriverName(),
                'database' => $databaseName,
            ];

        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get cache hit rate (simplified implementation).
     */
    private function getCacheHitRate(): ?float
    {
        // This would require implementing cache hit/miss tracking
        // For now, return null as it's not easily available
        return null;
    }

    /**
     * Get pending jobs count.
     */
    private function getPendingJobsCount(): int
    {
        try {
            // This would depend on your queue driver
            // For database queue, you could query the jobs table
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get failed jobs count.
     */
    private function getFailedJobsCount(): int
    {
        try {
            // Query failed_jobs table if it exists
            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                return DB::table('failed_jobs')->count();
            }
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get application uptime in seconds.
     */
    private function getApplicationUptime(): int
    {
        // This is a simplified implementation
        // In production, you might track this more accurately
        $startTime = Cache::get('app_start_time');
        if (!$startTime) {
            $startTime = now()->timestamp;
            Cache::put('app_start_time', $startTime, 86400);
        }

        return now()->timestamp - $startTime;
    }

    /**
     * Get OPcache statistics.
     */
    private function getOpcacheStats(): ?array
    {
        if (!function_exists('opcache_get_status')) {
            return null;
        }

        $status = opcache_get_status();
        if (!$status) {
            return null;
        }

        return [
            'enabled' => $status['opcache_enabled'] ?? false,
            'cache_full' => $status['cache_full'] ?? false,
            'restart_pending' => $status['restart_pending'] ?? false,
            'restart_in_progress' => $status['restart_in_progress'] ?? false,
            'memory_usage' => $status['memory_usage'] ?? [],
            'opcache_statistics' => $status['opcache_statistics'] ?? [],
        ];
    }

    /**
     * Store metrics for historical analysis.
     */
    private function storeMetrics(array $metrics): void
    {
        $key = self::CACHE_PREFIX . 'history:' . now()->format('Y-m-d-H');
        $existingMetrics = Cache::get($key, []);
        
        $existingMetrics[] = $metrics;
        
        // Keep only last 60 entries per hour
        if (count($existingMetrics) > 60) {
            $existingMetrics = array_slice($existingMetrics, -60);
        }
        
        Cache::put($key, $existingMetrics, self::METRICS_RETENTION_HOURS * 3600);
    }

    /**
     * Check performance metrics against thresholds and trigger alerts.
     */
    private function checkPerformanceAlerts(array $metrics): void
    {
        // Check memory usage
        if ($metrics['memory']['usage_percentage'] > 90) {
            $this->systemMonitor->recordError(
                'high_memory_usage',
                'Memory usage is critically high',
                $metrics['memory'],
                'critical'
            );
        } elseif ($metrics['memory']['usage_percentage'] > 80) {
            $this->systemMonitor->recordError(
                'high_memory_usage',
                'Memory usage is high',
                $metrics['memory'],
                'warning'
            );
        }

        // Check database response time
        if (isset($metrics['database']['connection_time_ms']) && $metrics['database']['connection_time_ms'] > 1000) {
            $this->systemMonitor->recordError(
                'slow_database_response',
                'Database response time is slow',
                ['response_time_ms' => $metrics['database']['connection_time_ms']],
                'warning'
            );
        }

        // Check cache response time
        if (isset($metrics['cache']['response_time_ms']) && $metrics['cache']['response_time_ms'] > 100) {
            $this->systemMonitor->recordError(
                'slow_cache_response',
                'Cache response time is slow',
                ['response_time_ms' => $metrics['cache']['response_time_ms']],
                'warning'
            );
        }

        // Check response time percentiles
        if (isset($metrics['response_times']['p95_ms']) && $metrics['response_times']['p95_ms'] > 5000) {
            $this->systemMonitor->recordError(
                'slow_response_times',
                '95th percentile response time is too high',
                $metrics['response_times'],
                'warning'
            );
        }
    }

    /**
     * Calculate percentile from sorted array.
     */
    private function calculatePercentile(array $sortedValues, float $percentile): float
    {
        $count = count($sortedValues);
        if ($count === 0) {
            return 0;
        }

        $index = ($percentile / 100) * ($count - 1);
        $lower = floor($index);
        $upper = ceil($index);

        if ($lower === $upper) {
            return $sortedValues[(int) $index];
        }

        $weight = $index - $lower;
        return $sortedValues[(int) $lower] * (1 - $weight) + $sortedValues[(int) $upper] * $weight;
    }

    /**
     * Parse memory limit string to bytes.
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;

        return match ($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * Get historical metrics for trend analysis.
     */
    public function getHistoricalMetrics(int $hours = 24): array
    {
        $metrics = [];
        $endTime = now();
        
        for ($i = 0; $i < $hours; $i++) {
            $time = $endTime->copy()->subHours($i);
            $key = self::CACHE_PREFIX . 'history:' . $time->format('Y-m-d-H');
            $hourlyMetrics = Cache::get($key, []);
            
            if (!empty($hourlyMetrics)) {
                $metrics[$time->format('Y-m-d H:00')] = $hourlyMetrics;
            }
        }
        
        return array_reverse($metrics, true);
    }
}