<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Monitoring\DatabaseQueryAnalyzer;
use App\Services\Monitoring\MonitoringDashboard;
use App\Services\Monitoring\PerformanceMetricsCollector;
use App\Services\Monitoring\SystemMonitor;
use App\Services\Monitoring\UserActivityTracker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MonitoringController extends Controller
{
    public function __construct(
        private readonly SystemMonitor $systemMonitor,
        private readonly PerformanceMetricsCollector $metricsCollector,
        private readonly DatabaseQueryAnalyzer $queryAnalyzer,
        private readonly UserActivityTracker $activityTracker,
        private readonly MonitoringDashboard $dashboard
    ) {}

    /**
     * Get system health status.
     */
    public function health(): JsonResponse
    {
        $health = $this->systemMonitor->checkSystemHealth();

        return response()->json([
            'status' => $health['status'],
            'timestamp' => $health['timestamp'],
            'checks' => $health['checks'],
        ]);
    }

    /**
     * Get comprehensive system status.
     */
    public function status(): JsonResponse
    {
        $status = $this->dashboard->getSystemStatus();

        return response()->json($status);
    }

    /**
     * Get performance metrics.
     */
    public function metrics(): JsonResponse
    {
        $metrics = $this->metricsCollector->collectMetrics();

        return response()->json([
            'timestamp' => $metrics['timestamp'],
            'collection_time_ms' => $metrics['collection_time_ms'],
            'metrics' => [
                'memory' => $metrics['memory'],
                'database' => $metrics['database'],
                'cache' => $metrics['cache'],
                'response_times' => $metrics['response_times'],
                'application' => $metrics['application'],
            ],
        ]);
    }

    /**
     * Get database analytics.
     */
    public function database(): JsonResponse
    {
        $queryStats = $this->queryAnalyzer->getQueryStatistics();
        $slowQueries = $this->queryAnalyzer->getSlowQueries();
        $recommendations = $this->queryAnalyzer->analyzeQueryPatterns();

        return response()->json([
            'statistics' => $queryStats,
            'slow_queries' => array_slice($slowQueries, -10), // Last 10 slow queries
            'recommendations' => $recommendations,
            'monitoring_status' => $this->queryAnalyzer->getCurrentQueryLog() ? 'active' : 'inactive',
        ]);
    }

    /**
     * Get user activity analytics.
     */
    public function activity(Request $request): JsonResponse
    {
        $days = min((int) $request->get('days', 7), 30); // Max 30 days
        $analytics = $this->activityTracker->getUserAnalytics($days);
        $realtimeActivity = $this->activityTracker->getRealtimeActivity(20);

        return response()->json([
            'period' => $analytics['period'],
            'daily_stats' => $analytics['daily_stats'],
            'top_actions' => $analytics['top_actions'],
            'top_features' => $analytics['top_features'],
            'user_engagement' => $analytics['user_engagement'],
            'realtime_activity' => $realtimeActivity,
        ]);
    }

    /**
     * Get performance trends.
     */
    public function trends(Request $request): JsonResponse
    {
        $hours = min((int) $request->get('hours', 24), 168); // Max 1 week
        $trends = $this->dashboard->getPerformanceTrends($hours);

        return response()->json($trends);
    }

    /**
     * Get dashboard data.
     */
    public function dashboard(): JsonResponse
    {
        $dashboardData = $this->dashboard->getDashboardData();

        return response()->json($dashboardData);
    }

    /**
     * Get alerts and recommendations.
     */
    public function alerts(): JsonResponse
    {
        $dashboardData = $this->dashboard->getDashboardData();

        return response()->json([
            'alerts' => $dashboardData['alerts'],
            'recommendations' => $dashboardData['recommendations'],
        ]);
    }

    /**
     * Export monitoring report.
     */
    public function export(): JsonResponse
    {
        $report = $this->dashboard->exportMonitoringReport();

        return response()->json($report);
    }

    /**
     * Get memory usage analysis.
     */
    public function memory(): JsonResponse
    {
        $metrics = $this->metricsCollector->collectMetrics();
        $memoryData = $metrics['memory'];

        // Get historical memory data
        $historicalData = [];
        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $key = "performance_metrics:history:{$hour}";
            $hourlyMetrics = Cache::get($key, []);

            if (!empty($hourlyMetrics)) {
                $latestMetric = end($hourlyMetrics);
                $historicalData[] = [
                    'timestamp' => $hour . ':00',
                    'usage_mb' => $latestMetric['memory']['current_usage_mb'] ?? 0,
                    'usage_percentage' => $latestMetric['memory']['usage_percentage'] ?? 0,
                    'peak_mb' => $latestMetric['memory']['peak_usage_mb'] ?? 0,
                ];
            }
        }

        // Analyze memory trends
        $trend = $this->analyzeMemoryTrend($historicalData);

        return response()->json([
            'current' => $memoryData,
            'historical' => $historicalData,
            'trend_analysis' => $trend,
            'recommendations' => $this->getMemoryRecommendations($memoryData, $trend),
        ]);
    }

    /**
     * Get error rate analysis.
     */
    public function errors(): JsonResponse
    {
        $errorData = [];
        $totalErrors = 0;
        $totalRequests = 0;

        // Collect error data for the last 24 hours
        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i)->format('Y-m-d-H');
            $errorKey = "system_monitor:errors:{$hour}";
            $requestKey = "system_monitor:requests:{$hour}";

            $hourlyErrors = Cache::get($errorKey, 0);
            $hourlyRequests = Cache::get($requestKey, 0);

            $totalErrors += $hourlyErrors;
            $totalRequests += $hourlyRequests;

            $errorRate = $hourlyRequests > 0 ? ($hourlyErrors / $hourlyRequests) * 100 : 0;

            $errorData[] = [
                'timestamp' => $hour . ':00',
                'errors' => $hourlyErrors,
                'requests' => $hourlyRequests,
                'error_rate' => round($errorRate, 2),
            ];
        }

        $overallErrorRate = $totalRequests > 0 ? ($totalErrors / $totalRequests) * 100 : 0;

        return response()->json([
            'summary' => [
                'total_errors' => $totalErrors,
                'total_requests' => $totalRequests,
                'error_rate' => round($overallErrorRate, 2),
                'period' => '24 hours',
            ],
            'hourly_data' => $errorData,
            'error_threshold_status' => $this->getErrorThresholdStatus($overallErrorRate),
        ]);
    }

    /**
     * Start database query monitoring.
     */
    public function startQueryMonitoring(): JsonResponse
    {
        $this->queryAnalyzer->startMonitoring();

        return response()->json([
            'message' => 'Database query monitoring started',
            'status' => 'active',
        ]);
    }

    /**
     * Stop database query monitoring.
     */
    public function stopQueryMonitoring(): JsonResponse
    {
        $this->queryAnalyzer->stopMonitoring();

        return response()->json([
            'message' => 'Database query monitoring stopped',
            'status' => 'inactive',
        ]);
    }

    /**
     * Clear monitoring cache.
     */
    public function clearCache(): JsonResponse
    {
        $this->dashboard->clearCache();

        return response()->json([
            'message' => 'Monitoring cache cleared successfully',
        ]);
    }

    /**
     * Analyze memory usage trend.
     */
    private function analyzeMemoryTrend(array $historicalData): array
    {
        if (count($historicalData) < 2) {
            return [
                'trend' => 'insufficient_data',
                'change_percentage' => 0,
                'prediction' => 'Unable to predict',
            ];
        }

        $recent = array_slice($historicalData, -6); // Last 6 hours
        $older = array_slice($historicalData, -12, 6); // 6 hours before that

        if (empty($older)) {
            return [
                'trend' => 'insufficient_data',
                'change_percentage' => 0,
                'prediction' => 'Unable to predict',
            ];
        }

        $recentAvg = array_sum(array_column($recent, 'usage_percentage')) / count($recent);
        $olderAvg = array_sum(array_column($older, 'usage_percentage')) / count($older);

        $changePercentage = (($recentAvg - $olderAvg) / $olderAvg) * 100;

        $trend = 'stable';
        if ($changePercentage > 10) {
            $trend = 'increasing';
        } elseif ($changePercentage < -10) {
            $trend = 'decreasing';
        }

        // Simple prediction based on trend
        $prediction = match ($trend) {
            'increasing' => $recentAvg > 80 ? 'Memory usage may reach critical levels' : 'Memory usage is trending upward',
            'decreasing' => 'Memory usage is improving',
            default => 'Memory usage is stable',
        };

        return [
            'trend' => $trend,
            'change_percentage' => round($changePercentage, 2),
            'recent_average' => round($recentAvg, 2),
            'older_average' => round($olderAvg, 2),
            'prediction' => $prediction,
        ];
    }

    /**
     * Get memory optimization recommendations.
     */
    private function getMemoryRecommendations(array $memoryData, array $trend): array
    {
        $recommendations = [];
        $usage = $memoryData['usage_percentage'] ?? 0;

        if ($usage > 90) {
            $recommendations[] = [
                'priority' => 'critical',
                'title' => 'Critical Memory Usage',
                'description' => 'Memory usage is at ' . $usage . '%',
                'action' => 'Immediate action required to prevent system instability',
            ];
        } elseif ($usage > 80) {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'High Memory Usage',
                'description' => 'Memory usage is at ' . $usage . '%',
                'action' => 'Consider optimizing memory usage or increasing memory limit',
            ];
        }

        if ($trend['trend'] === 'increasing' && $trend['change_percentage'] > 20) {
            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Memory Usage Trending Up',
                'description' => 'Memory usage has increased by ' . $trend['change_percentage'] . '% recently',
                'action' => 'Monitor closely and investigate potential memory leaks',
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'priority' => 'info',
                'title' => 'Memory Usage Normal',
                'description' => 'Memory usage is within acceptable limits',
                'action' => 'Continue monitoring',
            ];
        }

        return $recommendations;
    }

    /**
     * Get error threshold status.
     */
    private function getErrorThresholdStatus(float $errorRate): array
    {
        if ($errorRate > 5) {
            return [
                'status' => 'critical',
                'message' => 'Error rate is critically high',
                'threshold' => 5,
            ];
        } elseif ($errorRate > 2) {
            return [
                'status' => 'warning',
                'message' => 'Error rate is elevated',
                'threshold' => 2,
            ];
        }

        return [
            'status' => 'normal',
            'message' => 'Error rate is within acceptable limits',
            'threshold' => 2,
        ];
    }
}