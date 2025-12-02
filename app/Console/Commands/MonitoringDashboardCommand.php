<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Monitoring\MonitoringDashboard;
use Illuminate\Console\Command;

class MonitoringDashboardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:dashboard 
                            {--export : Export dashboard data to file}
                            {--trends : Show performance trends}
                            {--clear-cache : Clear dashboard cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display monitoring dashboard or export monitoring data';

    /**
     * Execute the console command.
     */
    public function handle(MonitoringDashboard $dashboard): int
    {
        try {
            if ($this->option('clear-cache')) {
                $dashboard->clearCache();
                $this->info('✅ Dashboard cache cleared');
                return Command::SUCCESS;
            }

            if ($this->option('export')) {
                return $this->exportDashboardData($dashboard);
            }

            if ($this->option('trends')) {
                return $this->showPerformanceTrends($dashboard);
            }

            return $this->showDashboard($dashboard);

        } catch (\Exception $e) {
            $this->error('❌ Dashboard operation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Show the monitoring dashboard.
     */
    private function showDashboard(MonitoringDashboard $dashboard): int
    {
        $this->info('📊 Loading monitoring dashboard...');
        
        $data = $dashboard->getDashboardData();
        
        $this->displaySystemHealth($data['system_health']);
        $this->displayPerformanceMetrics($data['performance_metrics']);
        $this->displayDatabaseAnalytics($data['database_analytics']);
        $this->displayUserActivity($data['user_activity']);
        $this->displayRecommendations($data['recommendations']);

        return Command::SUCCESS;
    }

    /**
     * Export dashboard data to file.
     */
    private function exportDashboardData(MonitoringDashboard $dashboard): int
    {
        $this->info('📤 Exporting monitoring report...');
        
        $report = $dashboard->exportMonitoringReport();
        $filename = 'monitoring_report_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('logs/' . $filename);
        
        file_put_contents($filepath, json_encode($report, JSON_PRETTY_PRINT));
        
        $this->info("✅ Monitoring report exported to: {$filepath}");
        $this->line("📁 File size: " . $this->formatBytes(filesize($filepath)));
        
        return Command::SUCCESS;
    }

    /**
     * Show performance trends.
     */
    private function showPerformanceTrends(MonitoringDashboard $dashboard): int
    {
        $this->info('📈 Loading performance trends...');
        
        $trends = $dashboard->getPerformanceTrends(24);
        
        $this->line('<fg=cyan>📈 Performance Trends (Last 24 Hours)</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        
        // Show metrics trends
        if (!empty($trends['metrics_history'])) {
            $this->line('<fg=yellow>Memory Usage Trend:</fg=yellow>');
            $this->displayMemoryTrend($trends['metrics_history']);
        }
        
        // Show query trends
        if (!empty($trends['query_history'])) {
            $this->line('<fg=yellow>Database Query Trend:</fg=yellow>');
            $this->displayQueryTrend($trends['query_history']);
        }
        
        // Show activity trends
        if (!empty($trends['activity_trends'])) {
            $this->line('<fg=yellow>User Activity Trend:</fg=yellow>');
            $this->displayActivityTrend($trends['activity_trends']);
        }
        
        return Command::SUCCESS;
    }

    /**
     * Display system health section.
     */
    private function displaySystemHealth(array $health): void
    {
        $this->newLine();
        $this->line('<fg=cyan>🏥 System Health</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $statusColor = match ($health['overall_status']) {
            'healthy' => 'green',
            'degraded' => 'yellow',
            'critical' => 'red',
            default => 'white',
        };

        $this->line(sprintf(
            '<fg=yellow>Overall Status:</fg=yellow> <fg=%s>%s</fg=%s>',
            $statusColor,
            strtoupper($health['overall_status']),
            $statusColor
        ));

        foreach ($health['components'] as $component => $status) {
            $componentColor = match ($status) {
                'healthy' => 'green',
                'degraded' => 'yellow',
                'critical' => 'red',
                default => 'white',
            };

            $this->line(sprintf(
                '  <fg=yellow>%s:</fg=yellow> <fg=%s>%s</fg=%s>',
                ucfirst($component),
                $componentColor,
                strtoupper($status),
                $componentColor
            ));
        }

        if (!empty($health['issues'])) {
            $this->line('<fg=red>Issues:</fg=red>');
            foreach ($health['issues'] as $issue) {
                $this->line('  <fg=red>⚠</fg=red> ' . $issue['issue'] . ' (' . $issue['component'] . ')');
            }
        }
    }

    /**
     * Display performance metrics section.
     */
    private function displayPerformanceMetrics(array $metrics): void
    {
        $this->newLine();
        $this->line('<fg=cyan>⚡ Performance Metrics</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Memory usage
        $memory = $metrics['memory_usage'];
        $memoryColor = $memory['usage_percentage'] > 80 ? 'red' : ($memory['usage_percentage'] > 60 ? 'yellow' : 'green');
        $this->line(sprintf(
            '<fg=yellow>Memory:</fg=yellow> <fg=%s>%s MB (%s%%)</fg=%s>',
            $memoryColor,
            $memory['current_mb'],
            $memory['usage_percentage'],
            $memoryColor
        ));

        // Response times
        if ($metrics['response_times']['average_ms']) {
            $rt = $metrics['response_times'];
            $this->line(sprintf(
                '<fg=yellow>Response Times:</fg=yellow> Avg: %s ms, P95: %s ms, P99: %s ms',
                $rt['average_ms'],
                $rt['p95_ms'],
                $rt['p99_ms']
            ));
        }

        // Database performance
        if ($metrics['database_performance']['connection_time_ms']) {
            $dbTime = $metrics['database_performance']['connection_time_ms'];
            $dbColor = $dbTime > 1000 ? 'red' : ($dbTime > 500 ? 'yellow' : 'green');
            $this->line(sprintf(
                '<fg=yellow>Database:</fg=yellow> <fg=%s>%s ms</fg=%s>',
                $dbColor,
                $dbTime,
                $dbColor
            ));
        }

        // Cache performance
        if ($metrics['cache_performance']['response_time_ms']) {
            $cacheTime = $metrics['cache_performance']['response_time_ms'];
            $cacheColor = $cacheTime > 100 ? 'red' : ($cacheTime > 50 ? 'yellow' : 'green');
            $this->line(sprintf(
                '<fg=yellow>Cache:</fg=yellow> <fg=%s>%s ms</fg=%s>',
                $cacheColor,
                $cacheTime,
                $cacheColor
            ));
        }
    }

    /**
     * Display database analytics section.
     */
    private function displayDatabaseAnalytics(array $analytics): void
    {
        $this->newLine();
        $this->line('<fg=cyan>🗄️ Database Analytics</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $stats = $analytics['query_statistics'];
        $this->line(sprintf(
            '<fg=yellow>Queries:</fg=yellow> %d total, %s ms avg, %d slow (%s%%)',
            $stats['total_queries'],
            $stats['average_time_ms'],
            $stats['slow_queries'],
            $stats['slow_query_percentage']
        ));

        if (!empty($analytics['query_types'])) {
            $this->line('<fg=yellow>Query Types:</fg=yellow>');
            foreach ($analytics['query_types'] as $type => $data) {
                $avgTime = $data['count'] > 0 ? round($data['total_time'] / $data['count'], 2) : 0;
                $this->line(sprintf('  %s: %d queries (%s ms avg)', $type, $data['count'], $avgTime));
            }
        }

        if (!empty($analytics['recent_slow_queries'])) {
            $this->line('<fg=red>Recent Slow Queries:</fg=red>');
            foreach (array_slice($analytics['recent_slow_queries'], 0, 3) as $query) {
                $this->line(sprintf('  %s ms: %s', $query['time_ms'], substr($query['sql'], 0, 80) . '...'));
            }
        }
    }

    /**
     * Display user activity section.
     */
    private function displayUserActivity(array $activity): void
    {
        $this->newLine();
        $this->line('<fg=cyan>👥 User Activity</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $engagement = $activity['engagement_metrics'];
        $this->line(sprintf(
            '<fg=yellow>Engagement:</fg=yellow> %d active users, %d sessions (%s avg per user)',
            $engagement['unique_active_users'],
            $engagement['total_sessions'],
            $engagement['average_sessions_per_user']
        ));

        if (!empty($activity['top_actions'])) {
            $this->line('<fg=yellow>Top Actions:</fg=yellow>');
            foreach (array_slice($activity['top_actions'], 0, 5, true) as $action => $count) {
                $this->line(sprintf('  %s: %d times', $action, $count));
            }
        }

        if (!empty($activity['top_features'])) {
            $this->line('<fg=yellow>Top Features:</fg=yellow>');
            foreach (array_slice($activity['top_features'], 0, 5, true) as $feature => $count) {
                $this->line(sprintf('  %s: %d uses', $feature, $count));
            }
        }
    }

    /**
     * Display recommendations section.
     */
    private function displayRecommendations(array $recommendations): void
    {
        if (empty($recommendations)) {
            return;
        }

        $this->newLine();
        $this->line('<fg=cyan>💡 Recommendations</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        foreach (array_slice($recommendations, 0, 5) as $rec) {
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
                $rec['title'] ?? $rec['issue'] ?? 'Recommendation',
                $rec['recommendation'] ?? $rec['description'] ?? 'No description'
            ));
        }
    }

    /**
     * Display memory trend.
     */
    private function displayMemoryTrend(array $history): void
    {
        $memoryData = [];
        foreach ($history as $hour => $metrics) {
            if (!empty($metrics)) {
                $latestMetric = end($metrics);
                if (isset($latestMetric['memory']['usage_percentage'])) {
                    $memoryData[$hour] = $latestMetric['memory']['usage_percentage'];
                }
            }
        }

        if (!empty($memoryData)) {
            $latest = array_slice($memoryData, -6, 6, true);
            foreach ($latest as $hour => $usage) {
                $color = $usage > 80 ? 'red' : ($usage > 60 ? 'yellow' : 'green');
                $this->line(sprintf('  %s: <fg=%s>%s%%</fg=%s>', $hour, $color, $usage, $color));
            }
        }
    }

    /**
     * Display query trend.
     */
    private function displayQueryTrend(array $history): void
    {
        $latest = array_slice($history, -6, 6, true);
        foreach ($latest as $hour => $stats) {
            if (isset($stats['total_queries'])) {
                $this->line(sprintf(
                    '  %s: %d queries (%s ms avg)',
                    $hour,
                    $stats['total_queries'],
                    $stats['average_time'] ?? 0
                ));
            }
        }
    }

    /**
     * Display activity trend.
     */
    private function displayActivityTrend(array $trends): void
    {
        $latest = array_slice($trends, -6, 6, true);
        foreach ($latest as $hour => $data) {
            $this->line(sprintf(
                '  %s: %d activities, %d users',
                $hour,
                $data['total_activities'],
                $data['unique_users']
            ));
        }
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
