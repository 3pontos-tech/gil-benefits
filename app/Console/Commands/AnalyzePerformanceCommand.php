<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Monitoring\DatabaseQueryAnalyzer;
use App\Services\Monitoring\PerformanceMetricsCollector;
use App\Services\Monitoring\SystemMonitor;
use App\Services\Monitoring\UserActivityTracker;
use Illuminate\Console\Command;

class AnalyzePerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:analyze-performance 
                            {--hours=24 : Number of hours to analyze}
                            {--export : Export analysis to file}
                            {--recommendations : Show performance recommendations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze application performance and provide recommendations';

    /**
     * Execute the console command.
     */
    public function handle(
        PerformanceMetricsCollector $metricsCollector,
        DatabaseQueryAnalyzer $queryAnalyzer,
        SystemMonitor $systemMonitor,
        UserActivityTracker $activityTracker
    ): int {
        $hours = (int) $this->option('hours');
        
        $this->info("🔍 Analyzing performance for the last {$hours} hours...");
        
        try {
            // Collect current metrics
            $currentMetrics = $metricsCollector->collectMetrics();
            $systemHealth = $systemMonitor->checkSystemHealth();
            $queryStats = $queryAnalyzer->getQueryStatistics();
            $userAnalytics = $activityTracker->getUserAnalytics(min($hours / 24, 30));

            // Get historical data
            $historicalMetrics = $metricsCollector->getHistoricalMetrics($hours);
            $historicalQueries = $queryAnalyzer->getHistoricalStatistics($hours);

            $this->displayPerformanceAnalysis($currentMetrics, $systemHealth, $queryStats, $userAnalytics);
            
            if ($this->option('recommendations')) {
                $this->displayRecommendations($queryAnalyzer, $currentMetrics);
            }

            if ($this->option('export')) {
                $this->exportAnalysis($currentMetrics, $systemHealth, $queryStats, $userAnalytics, $historicalMetrics, $historicalQueries);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Performance analysis failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function displayPerformanceAnalysis(array $metrics, array $health, array $queryStats, array $userAnalytics): void
    {
        $this->newLine();
        $this->line('<fg=cyan>📊 Performance Analysis Report</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // System Health
        $statusColor = match ($health['status']) {
            'healthy' => 'green',
            'degraded' => 'yellow',
            'critical' => 'red',
            default => 'white',
        };
        
        $this->line(sprintf(
            '<fg=yellow>System Status:</fg=yellow> <fg=%s>%s</fg=%s>',
            $statusColor,
            strtoupper($health['status']),
            $statusColor
        ));

        // Memory Analysis
        if (isset($metrics['memory'])) {
            $memory = $metrics['memory'];
            $memoryColor = $memory['usage_percentage'] > 80 ? 'red' : ($memory['usage_percentage'] > 60 ? 'yellow' : 'green');
            
            $this->line(sprintf(
                '<fg=yellow>Memory Usage:</fg=yellow> <fg=%s>%s MB (%s%%)</fg=%s> | Peak: %s MB',
                $memoryColor,
                $memory['current_usage_mb'],
                $memory['usage_percentage'],
                $memoryColor,
                $memory['peak_usage_mb']
            ));
        }

        // Response Time Analysis
        if (isset($metrics['response_times']) && $metrics['response_times']['sample_size'] > 0) {
            $rt = $metrics['response_times'];
            $avgColor = $rt['average_ms'] > 2000 ? 'red' : ($rt['average_ms'] > 1000 ? 'yellow' : 'green');
            
            $this->line(sprintf(
                '<fg=yellow>Response Times:</fg=yellow> Avg: <fg=%s>%s ms</fg=%s> | P95: %s ms | P99: %s ms',
                $avgColor,
                $rt['average_ms'],
                $avgColor,
                $rt['p95_ms'],
                $rt['p99_ms']
            ));
        }

        // Database Analysis
        $this->line(sprintf(
            '<fg=yellow>Database:</fg=yellow> %d queries | %s ms avg | %d slow (%s%%)',
            $queryStats['total_queries'] ?? 0,
            $queryStats['average_time'] ?? 0,
            $queryStats['slow_queries'] ?? 0,
            $queryStats['slow_query_percentage'] ?? 0
        ));

        // User Activity Analysis
        $engagement = $userAnalytics['user_engagement'] ?? [];
        $this->line(sprintf(
            '<fg=yellow>User Activity:</fg=yellow> %d active users | %d sessions | %s avg sessions/user',
            $engagement['unique_active_users'] ?? 0,
            $engagement['total_sessions'] ?? 0,
            $engagement['average_sessions_per_user'] ?? 0
        ));

        // Top Actions
        if (!empty($userAnalytics['top_actions'])) {
            $this->newLine();
            $this->line('<fg=yellow>Top User Actions:</fg=yellow>');
            foreach (array_slice($userAnalytics['top_actions'], 0, 5, true) as $action => $count) {
                $this->line("  {$action}: {$count} times");
            }
        }

        // Query Type Breakdown
        if (!empty($queryStats['by_type'])) {
            $this->newLine();
            $this->line('<fg=yellow>Query Type Breakdown:</fg=yellow>');
            foreach ($queryStats['by_type'] as $type => $data) {
                $avgTime = $data['count'] > 0 ? round($data['total_time'] / $data['count'], 2) : 0;
                $this->line("  {$type}: {$data['count']} queries ({$avgTime} ms avg)");
            }
        }
    }

    private function displayRecommendations(DatabaseQueryAnalyzer $queryAnalyzer, array $metrics): void
    {
        $this->newLine();
        $this->line('<fg=cyan>💡 Performance Recommendations</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $recommendations = [];

        // Database recommendations
        $queryRecommendations = $queryAnalyzer->analyzeQueryPatterns();
        $recommendations = array_merge($recommendations, $queryRecommendations);

        // Memory recommendations
        if (isset($metrics['memory']) && $metrics['memory']['usage_percentage'] > 80) {
            $recommendations[] = [
                'type' => 'memory',
                'priority' => 'high',
                'issue' => 'High Memory Usage',
                'recommendation' => 'Memory usage is at ' . $metrics['memory']['usage_percentage'] . '%. Consider optimizing memory usage or increasing memory limit.',
            ];
        }

        // Response time recommendations
        if (isset($metrics['response_times']) && $metrics['response_times']['p95_ms'] > 3000) {
            $recommendations[] = [
                'type' => 'performance',
                'priority' => 'medium',
                'issue' => 'Slow Response Times',
                'recommendation' => '95th percentile response time is ' . $metrics['response_times']['p95_ms'] . 'ms. Consider optimizing slow endpoints and implementing caching.',
            ];
        }

        // Cache recommendations
        if (isset($metrics['cache']) && $metrics['cache']['response_time_ms'] > 100) {
            $recommendations[] = [
                'type' => 'cache',
                'priority' => 'medium',
                'issue' => 'Slow Cache Response',
                'recommendation' => 'Cache response time is ' . $metrics['cache']['response_time_ms'] . 'ms. Check cache configuration and consider cache optimization.',
            ];
        }

        if (empty($recommendations)) {
            $this->line('<fg=green>✅ No performance issues detected. System is performing well!</fg=green>');
            return;
        }

        // Sort by priority
        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return ($priorityOrder[$a['priority']] ?? 4) <=> ($priorityOrder[$b['priority']] ?? 4);
        });

        foreach (array_slice($recommendations, 0, 10) as $rec) {
            $priorityColor = match ($rec['priority']) {
                'critical' => 'red',
                'high' => 'red',
                'medium' => 'yellow',
                'low' => 'green',
                default => 'white',
            };

            $this->line(sprintf(
                '<fg=%s>[%s]</fg=%s> <fg=yellow>%s:</fg=yellow> %s',
                $priorityColor,
                strtoupper($rec['priority']),
                $priorityColor,
                $rec['issue'] ?? 'Performance Issue',
                $rec['recommendation']
            ));
        }
    }

    private function exportAnalysis(array $metrics, array $health, array $queryStats, array $userAnalytics, array $historicalMetrics, array $historicalQueries): void
    {
        $report = [
            'generated_at' => now()->toISOString(),
            'analysis_period_hours' => $this->option('hours'),
            'current_metrics' => $metrics,
            'system_health' => $health,
            'query_statistics' => $queryStats,
            'user_analytics' => $userAnalytics,
            'historical_data' => [
                'metrics' => $historicalMetrics,
                'queries' => $historicalQueries,
            ],
        ];

        $filename = 'performance_analysis_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('logs/' . $filename);

        file_put_contents($filepath, json_encode($report, JSON_PRETTY_PRINT));

        $this->info("📁 Performance analysis exported to: {$filename}");
        $this->line("File size: " . $this->formatBytes(filesize($filepath)));
    }

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
