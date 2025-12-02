<?php

declare(strict_types=1);

namespace App\Services\Monitoring;

use App\Services\Logging\StructuredLogger;
use Illuminate\Support\Facades\Cache;

class MonitoringDashboard
{
    private const CACHE_PREFIX = 'monitoring_dashboard:';
    private const DASHBOARD_CACHE_TTL = 300; // 5 minutes

    public function __construct(
        private readonly SystemMonitor $systemMonitor,
        private readonly PerformanceMetricsCollector $metricsCollector,
        private readonly DatabaseQueryAnalyzer $queryAnalyzer,
        private readonly UserActivityTracker $activityTracker,
        private readonly StructuredLogger $logger
    ) {}

    /**
     * Get comprehensive dashboard data.
     */
    public function getDashboardData(bool $useCache = true): array
    {
        $cacheKey = self::CACHE_PREFIX . 'dashboard_data';

        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $dashboardData = [
            'generated_at' => now()->toISOString(),
            'system_health' => $this->getSystemHealthSummary(),
            'performance_metrics' => $this->getPerformanceMetricsSummary(),
            'database_analytics' => $this->getDatabaseAnalyticsSummary(),
            'user_activity' => $this->getUserActivitySummary(),
            'alerts' => $this->getActiveAlerts(),
            'recommendations' => $this->getRecommendations(),
        ];

        if ($useCache) {
            Cache::put($cacheKey, $dashboardData, self::DASHBOARD_CACHE_TTL);
        }

        return $dashboardData;
    }

    /**
     * Get system health summary.
     */
    private function getSystemHealthSummary(): array
    {
        $healthCheck = $this->systemMonitor->checkSystemHealth();

        return [
            'overall_status' => $healthCheck['status'],
            'components' => [
                'database' => $healthCheck['checks']['database']['status'] ?? 'unknown',
                'cache' => $healthCheck['checks']['cache']['status'] ?? 'unknown',
                'storage' => $healthCheck['checks']['storage']['status'] ?? 'unknown',
                'memory' => $healthCheck['checks']['memory']['status'] ?? 'unknown',
                'queue' => $healthCheck['checks']['queue']['status'] ?? 'unknown',
            ],
            'issues' => $this->extractIssues($healthCheck['checks']),
            'last_check' => $healthCheck['timestamp'] ?? now()->toISOString(),
        ];
    }

    /**
     * Get performance metrics summary.
     */
    private function getPerformanceMetricsSummary(): array
    {
        $metrics = $this->metricsCollector->collectMetrics();

        return [
            'response_times' => [
                'average_ms' => $metrics['response_times']['average_ms'] ?? null,
                'p95_ms' => $metrics['response_times']['p95_ms'] ?? null,
                'p99_ms' => $metrics['response_times']['p99_ms'] ?? null,
            ],
            'memory_usage' => [
                'current_mb' => $metrics['memory']['current_usage_mb'] ?? 0,
                'peak_mb' => $metrics['memory']['peak_usage_mb'] ?? 0,
                'usage_percentage' => $metrics['memory']['usage_percentage'] ?? 0,
            ],
            'database_performance' => [
                'connection_time_ms' => $metrics['database']['connection_time_ms'] ?? null,
                'status' => $metrics['database']['connection_status'] ?? 'unknown',
            ],
            'cache_performance' => [
                'response_time_ms' => $metrics['cache']['response_time_ms'] ?? null,
                'status' => $metrics['cache']['status'] ?? 'unknown',
            ],
        ];
    }

    /**
     * Get database analytics summary.
     */
    private function getDatabaseAnalyticsSummary(): array
    {
        $queryStats = $this->queryAnalyzer->getQueryStatistics();
        $slowQueries = $this->queryAnalyzer->getSlowQueries();

        return [
            'query_statistics' => [
                'total_queries' => $queryStats['total_queries'] ?? 0,
                'average_time_ms' => $queryStats['average_time'] ?? 0,
                'slow_queries' => $queryStats['slow_queries'] ?? 0,
                'slow_query_percentage' => $queryStats['slow_query_percentage'] ?? 0,
            ],
            'query_types' => $queryStats['by_type'] ?? [],
            'recent_slow_queries' => array_slice($slowQueries, -5), // Last 5 slow queries
            'recommendations' => $this->queryAnalyzer->analyzeQueryPatterns(),
        ];
    }

    /**
     * Get user activity summary.
     */
    private function getUserActivitySummary(): array
    {
        $analytics = $this->activityTracker->getUserAnalytics(7);
        $realtimeActivity = $this->activityTracker->getRealtimeActivity(10);

        return [
            'daily_active_users' => $analytics['user_engagement']['daily_active_users'] ?? [],
            'top_actions' => array_slice($analytics['top_actions'] ?? [], 0, 5, true),
            'top_features' => array_slice($analytics['top_features'] ?? [], 0, 5, true),
            'engagement_metrics' => [
                'unique_active_users' => $analytics['user_engagement']['unique_active_users'] ?? 0,
                'total_sessions' => $analytics['user_engagement']['total_sessions'] ?? 0,
                'average_sessions_per_user' => $analytics['user_engagement']['average_sessions_per_user'] ?? 0,
            ],
            'recent_activity' => $realtimeActivity,
        ];
    }

    /**
     * Get active alerts.
     */
    private function getActiveAlerts(): array
    {
        // This would typically come from a persistent alert storage
        // For now, we'll return recent alerts from logs
        return [
            'critical' => $this->getRecentAlerts('critical'),
            'error' => $this->getRecentAlerts('error'),
            'warning' => $this->getRecentAlerts('warning'),
        ];
    }

    /**
     * Get system recommendations.
     */
    private function getRecommendations(): array
    {
        $recommendations = [];

        // Get performance recommendations
        $performanceRecs = $this->getPerformanceRecommendations();
        $recommendations = array_merge($recommendations, $performanceRecs);

        // Get database recommendations
        $databaseRecs = $this->queryAnalyzer->analyzeQueryPatterns();
        $recommendations = array_merge($recommendations, $databaseRecs);

        // Sort by priority
        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return ($priorityOrder[$a['priority']] ?? 4) <=> ($priorityOrder[$b['priority']] ?? 4);
        });

        return array_slice($recommendations, 0, 10); // Top 10 recommendations
    }

    /**
     * Get performance-based recommendations.
     */
    private function getPerformanceRecommendations(): array
    {
        $recommendations = [];
        $metrics = $this->metricsCollector->collectMetrics();

        // Memory usage recommendations
        if (($metrics['memory']['usage_percentage'] ?? 0) > 80) {
            $recommendations[] = [
                'type' => 'memory',
                'priority' => 'high',
                'title' => 'High Memory Usage',
                'description' => 'Memory usage is at ' . ($metrics['memory']['usage_percentage'] ?? 0) . '%',
                'recommendation' => 'Consider optimizing memory usage or increasing memory limit',
                'action' => 'review_memory_usage',
            ];
        }

        // Response time recommendations
        if (($metrics['response_times']['p95_ms'] ?? 0) > 3000) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'medium',
                'title' => 'Slow Response Times',
                'description' => '95th percentile response time is ' . ($metrics['response_times']['p95_ms'] ?? 0) . 'ms',
                'recommendation' => 'Optimize slow endpoints and consider caching',
                'action' => 'optimize_response_times',
            ];
        }

        // Database performance recommendations
        if (($metrics['database_performance']['connection_time_ms'] ?? 0) > 500) {
            $recommendations[] = [
                'type' => 'database',
                'priority' => 'medium',
                'title' => 'Slow Database Connection',
                'description' => 'Database connection time is ' . ($metrics['database_performance']['connection_time_ms'] ?? 0) . 'ms',
                'recommendation' => 'Check database performance and connection pooling',
                'action' => 'optimize_database',
            ];
        }

        return $recommendations;
    }

    /**
     * Get recent alerts by severity.
     */
    private function getRecentAlerts(string $severity): array
    {
        // This is a simplified implementation
        // In production, you would query a persistent alert storage
        return [];
    }

    /**
     * Extract issues from health check results.
     */
    private function extractIssues(array $checks): array
    {
        $issues = [];

        foreach ($checks as $component => $result) {
            if (isset($result['issues']) && !empty($result['issues'])) {
                foreach ($result['issues'] as $issue) {
                    $issues[] = [
                        'component' => $component,
                        'issue' => $issue,
                        'status' => $result['status'] ?? 'unknown',
                    ];
                }
            }
        }

        return $issues;
    }

    /**
     * Get historical performance trends.
     */
    public function getPerformanceTrends(int $hours = 24): array
    {
        return [
            'metrics_history' => $this->metricsCollector->getHistoricalMetrics($hours),
            'query_history' => $this->queryAnalyzer->getHistoricalStatistics($hours),
            'activity_trends' => $this->getActivityTrends($hours),
        ];
    }

    /**
     * Get activity trends for the specified period.
     */
    private function getActivityTrends(int $hours): array
    {
        $trends = [];
        $endTime = now();

        for ($i = 0; $i < $hours; $i++) {
            $time = $endTime->copy()->subHours($i);
            $hourKey = 'user_activity:hourly:' . $time->format('Y-m-d-H');
            $activities = Cache::get($hourKey, []);

            $trends[$time->format('Y-m-d H:00')] = [
                'total_activities' => count($activities),
                'unique_users' => count(array_unique(array_column($activities, 'user_id'))),
                'unique_sessions' => count(array_unique(array_column($activities, 'session_id'))),
            ];
        }

        return array_reverse($trends, true);
    }

    /**
     * Get system status for external monitoring.
     */
    public function getSystemStatus(): array
    {
        $health = $this->systemMonitor->checkSystemHealth();
        $metrics = $this->metricsCollector->collectMetrics();

        return [
            'status' => $health['status'],
            'timestamp' => now()->toISOString(),
            'version' => app()->version(),
            'environment' => app()->environment(),
            'uptime_seconds' => $metrics['application']['uptime_seconds'] ?? 0,
            'memory_usage_mb' => $metrics['memory']['current_usage_mb'] ?? 0,
            'database_status' => $health['checks']['database']['status'] ?? 'unknown',
            'cache_status' => $health['checks']['cache']['status'] ?? 'unknown',
        ];
    }

    /**
     * Export comprehensive monitoring report.
     */
    public function exportMonitoringReport(): array
    {
        return [
            'generated_at' => now()->toISOString(),
            'report_type' => 'comprehensive_monitoring',
            'dashboard_data' => $this->getDashboardData(false),
            'performance_trends' => $this->getPerformanceTrends(24),
            'system_status' => $this->getSystemStatus(),
            'detailed_metrics' => $this->metricsCollector->collectMetrics(),
            'query_analysis' => $this->queryAnalyzer->exportAnalysisReport(),
            'activity_analytics' => $this->activityTracker->exportAnalyticsReport(7),
        ];
    }

    /**
     * Clear dashboard cache.
     */
    public function clearCache(): void
    {
        $pattern = self::CACHE_PREFIX . '*';
        $keys = Cache::getRedis()->keys($pattern);

        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }

        $this->logger->logSystemEvent('monitoring_dashboard_cache_cleared', [
            'cleared_keys' => count($keys),
        ]);
    }
}