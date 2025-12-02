<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Services\Logging\StructuredLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemMonitor
{
    private const CACHE_PREFIX = 'system_monitor:';

    private const ALERT_COOLDOWN = 300; // 5 minutes

    public function __construct(
        private readonly StructuredLogger $logger,
        private readonly AlertManager $alertManager
    ) {}

    public function checkSystemHealth(): array
    {
        $checks = [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'memory' => $this->checkMemoryUsage(),
            'queue' => $this->checkQueueHealth(),
        ];

        $overallHealth = $this->calculateOverallHealth($checks);

        $this->logger->logSystemEvent('health_check_completed', [
            'overall_health' => $overallHealth,
            'individual_checks' => $checks,
        ]);

        // Trigger alerts for critical issues
        $this->processHealthAlerts($checks);

        return [
            'status' => $overallHealth,
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ];
    }

    public function recordError(
        string $errorType,
        string $message,
        array $context = [],
        string $severity = 'error'
    ): void {
        $errorKey = $this->getErrorKey($errorType);
        $currentCount = Cache::get($errorKey, 0);
        $newCount = $currentCount + 1;

        // Store error count with 1-hour expiration
        Cache::put($errorKey, $newCount, 3600);

        $this->logger->logSystemEvent('error_recorded', [
            'error_type' => $errorType,
            'message' => $message,
            'severity' => $severity,
            'count' => $newCount,
            'context' => $context,
        ], $severity);

        // Check if we need to send an alert
        $this->checkErrorThreshold($errorType, $newCount, $severity, $message, $context);
    }

    public function recordPerformanceMetric(
        string $metric,
        float $value,
        array $context = []
    ): void {
        $metricKey = $this->getMetricKey($metric);

        // Store recent values for trend analysis
        $recentValues = Cache::get($metricKey, []);
        $recentValues[] = [
            'value' => $value,
            'timestamp' => now()->timestamp,
        ];

        // Keep only last 100 values
        if (count($recentValues) > 100) {
            $recentValues = array_slice($recentValues, -100);
        }

        Cache::put($metricKey, $recentValues, 3600);

        $this->logger->logPerformanceEvent($metric, $value / 1000, $context);

        // Check performance thresholds
        $this->checkPerformanceThreshold($metric, $value, $recentValues);
    }

    private function checkDatabaseHealth(): array
    {
        try {
            $startTime = microtime(true);

            // Test basic connectivity
            DB::select('SELECT 1');

            $responseTime = (microtime(true) - $startTime) * 1000;

            // Check for slow queries
            $slowQueries = $this->getSlowQueryCount();

            // Check connection count
            $connections = $this->getDatabaseConnections();

            $status = 'healthy';
            $issues = [];

            if ($responseTime > 1000) { // 1 second
                $status = 'degraded';
                $issues[] = 'High database response time';
            }

            if ($slowQueries > 10) {
                $status = 'degraded';
                $issues[] = 'High number of slow queries';
            }

            return [
                'status' => $status,
                'response_time_ms' => round($responseTime, 2),
                'slow_queries' => $slowQueries,
                'connections' => $connections,
                'issues' => $issues,
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Database connection failed'],
            ];
        }
    }

    private function checkCacheHealth(): array
    {
        try {
            $startTime = microtime(true);

            $testKey = 'health_check_' . uniqid();
            $testValue = 'test_value';

            // Test cache write
            Cache::put($testKey, $testValue, 60);

            // Test cache read
            $retrievedValue = Cache::get($testKey);

            // Clean up
            Cache::forget($testKey);

            $responseTime = (microtime(true) - $startTime) * 1000;

            $status = 'healthy';
            $issues = [];

            if ($retrievedValue !== $testValue) {
                $status = 'critical';
                $issues[] = 'Cache read/write test failed';
            } elseif ($responseTime > 100) { // 100ms
                $status = 'degraded';
                $issues[] = 'High cache response time';
            }

            return [
                'status' => $status,
                'response_time_ms' => round($responseTime, 2),
                'issues' => $issues,
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Cache system failed'],
            ];
        }
    }

    private function checkStorageHealth(): array
    {
        try {
            $issues = [];
            $status = 'healthy';

            // Check disk space
            $diskSpace = disk_free_space(storage_path());
            $totalSpace = disk_total_space(storage_path());
            $usagePercent = (($totalSpace - $diskSpace) / $totalSpace) * 100;

            if ($usagePercent > 90) {
                $status = 'critical';
                $issues[] = 'Disk space critically low';
            } elseif ($usagePercent > 80) {
                $status = 'degraded';
                $issues[] = 'Disk space running low';
            }

            // Test file write
            $testFile = storage_path('logs/health_check_' . uniqid() . '.tmp');
            $writeSuccess = file_put_contents($testFile, 'test') !== false;

            if ($writeSuccess) {
                unlink($testFile);
            } else {
                $status = 'critical';
                $issues[] = 'Cannot write to storage';
            }

            return [
                'status' => $status,
                'disk_usage_percent' => round($usagePercent, 2),
                'free_space_mb' => round($diskSpace / 1024 / 1024, 2),
                'write_test' => $writeSuccess,
                'issues' => $issues,
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Storage system check failed'],
            ];
        }
    }

    private function checkMemoryUsage(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));

        $usagePercent = ($memoryUsage / $memoryLimit) * 100;
        $peakPercent = ($memoryPeak / $memoryLimit) * 100;

        $status = 'healthy';
        $issues = [];

        if ($usagePercent > 90) {
            $status = 'critical';
            $issues[] = 'Memory usage critically high';
        } elseif ($usagePercent > 80) {
            $status = 'degraded';
            $issues[] = 'Memory usage high';
        }

        return [
            'status' => $status,
            'usage_percent' => round($usagePercent, 2),
            'peak_percent' => round($peakPercent, 2),
            'usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'peak_mb' => round($memoryPeak / 1024 / 1024, 2),
            'limit_mb' => round($memoryLimit / 1024 / 1024, 2),
            'issues' => $issues,
        ];
    }

    private function checkQueueHealth(): array
    {
        try {
            // This is a basic implementation - you might want to customize based on your queue driver
            $status = 'healthy';
            $issues = [];

            // Check if queue workers are running (this is driver-dependent)
            // For now, we'll just return a basic status

            return [
                'status' => $status,
                'issues' => $issues,
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'critical',
                'error' => $e->getMessage(),
                'issues' => ['Queue system check failed'],
            ];
        }
    }

    private function calculateOverallHealth(array $checks): string
    {
        $statuses = array_column($checks, 'status');

        if (in_array('critical', $statuses)) {
            return 'critical';
        }

        if (in_array('degraded', $statuses)) {
            return 'degraded';
        }

        return 'healthy';
    }

    private function processHealthAlerts(array $checks): void
    {
        foreach ($checks as $checkName => $result) {
            if ($result['status'] === 'critical') {
                $this->sendAlert(
                    "Critical system issue: {$checkName}",
                    $result,
                    'critical'
                );
            } elseif ($result['status'] === 'degraded') {
                $this->sendAlert(
                    "System degradation: {$checkName}",
                    $result,
                    'warning'
                );
            }
        }
    }

    private function checkErrorThreshold(
        string $errorType,
        int $count,
        string $severity,
        string $message,
        array $context
    ): void {
        $thresholds = [
            'critical' => 1,
            'error' => 5,
            'warning' => 10,
        ];

        $threshold = $thresholds[$severity] ?? 10;

        if ($count >= $threshold) {
            $this->sendAlert(
                "Error threshold exceeded: {$errorType}",
                [
                    'error_type' => $errorType,
                    'count' => $count,
                    'threshold' => $threshold,
                    'severity' => $severity,
                    'message' => $message,
                    'context' => $context,
                ],
                $severity
            );
        }
    }

    private function checkPerformanceThreshold(string $metric, float $value, array $recentValues): void
    {
        $thresholds = [
            'response_time' => 5000, // 5 seconds
            'memory_usage' => 512 * 1024 * 1024, // 512MB
            'query_time' => 1000, // 1 second
        ];

        $threshold = $thresholds[$metric] ?? null;

        if ($threshold && $value > $threshold) {
            // Calculate trend
            $trend = $this->calculateTrend($recentValues);

            $this->sendAlert(
                "Performance threshold exceeded: {$metric}",
                [
                    'metric' => $metric,
                    'value' => $value,
                    'threshold' => $threshold,
                    'trend' => $trend,
                ],
                'warning'
            );
        }
    }

    private function sendAlert(string $title, array $data, string $severity): void
    {
        $alertKey = $this->getAlertKey($title);

        // Check cooldown period
        if (Cache::has($alertKey)) {
            return;
        }

        // Set cooldown
        Cache::put($alertKey, true, self::ALERT_COOLDOWN);

        $this->alertManager->sendAlert($title, $data, $severity);
    }

    private function getErrorKey(string $errorType): string
    {
        return self::CACHE_PREFIX . 'errors:' . $errorType;
    }

    private function getMetricKey(string $metric): string
    {
        return self::CACHE_PREFIX . 'metrics:' . $metric;
    }

    private function getAlertKey(string $title): string
    {
        return self::CACHE_PREFIX . 'alerts:' . md5($title);
    }

    private function getSlowQueryCount(): int
    {
        // This would depend on your database driver and configuration
        // For SQLite, we can't easily get slow query count, so return 0
        return 0;
    }

    private function getDatabaseConnections(): int
    {
        // This would depend on your database driver
        // For SQLite, return 1 as it's file-based
        return 1;
    }

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

    private function calculateTrend(array $values): string
    {
        if (count($values) < 2) {
            return 'insufficient_data';
        }

        $recent = array_slice($values, -10);
        $older = array_slice($values, -20, 10);

        if (empty($older)) {
            return 'insufficient_data';
        }

        $recentAvg = array_sum(array_column($recent, 'value')) / count($recent);
        $olderAvg = array_sum(array_column($older, 'value')) / count($older);

        $change = (($recentAvg - $olderAvg) / $olderAvg) * 100;

        if ($change > 20) {
            return 'increasing';
        } elseif ($change < -20) {
            return 'decreasing';
        }

        return 'stable';
    }
}
