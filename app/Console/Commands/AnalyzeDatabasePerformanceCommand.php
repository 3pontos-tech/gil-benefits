<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Monitoring\DatabaseQueryAnalyzer;
use App\Services\Monitoring\SystemMonitor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class AnalyzeDatabasePerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:analyze-database 
                            {--start : Start query monitoring}
                            {--stop : Stop query monitoring}
                            {--report : Generate performance report}
                            {--recommendations : Show optimization recommendations}
                            {--export : Export analysis to file}
                            {--clear : Clear analysis data}
                            {--hours=24 : Hours of data to analyze}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze database performance and provide optimization recommendations';

    /**
     * Execute the console command.
     */
    public function handle(
        DatabaseQueryAnalyzer $analyzer,
        SystemMonitor $systemMonitor
    ): int {
        try {
            if ($this->option('start')) {
                return $this->startMonitoring($analyzer);
            }

            if ($this->option('stop')) {
                return $this->stopMonitoring($analyzer);
            }

            if ($this->option('clear')) {
                return $this->clearAnalysisData($analyzer);
            }

            if ($this->option('export')) {
                return $this->exportAnalysis($analyzer);
            }

            if ($this->option('recommendations')) {
                return $this->showRecommendations($analyzer);
            }

            if ($this->option('report')) {
                return $this->generateReport($analyzer);
            }

            // Default: show current statistics
            return $this->showCurrentStatistics($analyzer);

        } catch (\Exception $e) {
            $this->error('❌ Database analysis failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Start query monitoring.
     */
    private function startMonitoring(DatabaseQueryAnalyzer $analyzer): int
    {
        $this->info('🔍 Starting database query monitoring...');
        
        $analyzer->startMonitoring();
        
        $this->info('✅ Database query monitoring started successfully');
        $this->line('📊 Queries will be analyzed for performance and optimization opportunities');
        
        return Command::SUCCESS;
    }

    /**
     * Stop query monitoring.
     */
    private function stopMonitoring(DatabaseQueryAnalyzer $analyzer): int
    {
        $this->info('⏹️ Stopping database query monitoring...');
        
        $analyzer->stopMonitoring();
        
        $this->info('✅ Database query monitoring stopped');
        
        return Command::SUCCESS;
    }

    /**
     * Show current statistics.
     */
    private function showCurrentStatistics(DatabaseQueryAnalyzer $analyzer): int
    {
        $this->info('📊 Current Database Performance Statistics');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $stats = $analyzer->getQueryStatistics();
        
        if ($stats['total_queries'] === 0) {
            $this->warn('⚠️ No query data available. Start monitoring with --start option.');
            return Command::SUCCESS;
        }

        // Display basic statistics
        $this->displayBasicStatistics($stats);
        
        // Display query type breakdown
        $this->displayQueryTypeBreakdown($stats);
        
        // Display slow queries
        $this->displaySlowQueries($analyzer);
        
        return Command::SUCCESS;
    }

    /**
     * Generate comprehensive report.
     */
    private function generateReport(DatabaseQueryAnalyzer $analyzer): int
    {
        $hours = (int) $this->option('hours');
        
        $this->info("📈 Generating Database Performance Report (Last {$hours} hours)");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Current statistics
        $this->showCurrentStatistics($analyzer);
        
        // Historical data
        $this->displayHistoricalData($analyzer, $hours);
        
        // Performance trends
        $this->displayPerformanceTrends($analyzer, $hours);
        
        // Recommendations
        $this->showRecommendations($analyzer);
        
        return Command::SUCCESS;
    }

    /**
     * Show optimization recommendations.
     */
    private function showRecommendations(DatabaseQueryAnalyzer $analyzer): int
    {
        $this->info('💡 Database Optimization Recommendations');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $recommendations = $analyzer->analyzeQueryPatterns();
        
        if (empty($recommendations)) {
            $this->info('✅ No specific recommendations at this time. Database performance appears optimal.');
            return Command::SUCCESS;
        }

        foreach ($recommendations as $index => $rec) {
            $priorityColor = match ($rec['priority']) {
                'critical' => 'red',
                'high' => 'red',
                'medium' => 'yellow',
                'low' => 'green',
                default => 'white',
            };

            $this->newLine();
            $this->line(sprintf(
                '<fg=%s>[%s] %s</fg=%s>',
                $priorityColor,
                strtoupper($rec['priority']),
                $rec['issue'] ?? 'Optimization Opportunity',
                $priorityColor
            ));
            
            $this->line('📝 ' . ($rec['description'] ?? 'No description available'));
            $this->line('🔧 ' . ($rec['recommendation'] ?? 'No recommendation available'));
            
            if (isset($rec['count'])) {
                $this->line("📊 Occurrences: {$rec['count']}");
            }
            
            if (isset($rec['avg_time'])) {
                $this->line("⏱️ Average time: {$rec['avg_time']} ms");
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Export analysis to file.
     */
    private function exportAnalysis(DatabaseQueryAnalyzer $analyzer): int
    {
        $this->info('📤 Exporting database analysis report...');
        
        $report = $analyzer->exportAnalysisReport();
        $filename = 'database_analysis_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('logs/' . $filename);
        
        file_put_contents($filepath, json_encode($report, JSON_PRETTY_PRINT));
        
        $this->info("✅ Analysis report exported to: {$filepath}");
        $this->line("📁 File size: " . $this->formatBytes(filesize($filepath)));
        
        return Command::SUCCESS;
    }

    /**
     * Clear analysis data.
     */
    private function clearAnalysisData(DatabaseQueryAnalyzer $analyzer): int
    {
        if (!$this->confirm('Are you sure you want to clear all database analysis data?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $this->info('🧹 Clearing database analysis data...');
        
        $analyzer->clearStatistics();
        
        $this->info('✅ Database analysis data cleared successfully');
        
        return Command::SUCCESS;
    }

    /**
     * Display basic statistics.
     */
    private function displayBasicStatistics(array $stats): void
    {
        $this->newLine();
        $this->line('<fg=cyan>📊 Query Statistics</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->line(sprintf(
            '<fg=yellow>Total Queries:</fg=yellow> %d',
            $stats['total_queries']
        ));

        $this->line(sprintf(
            '<fg=yellow>Average Time:</fg=yellow> %s ms',
            $stats['average_time'] ?? 0
        ));

        $this->line(sprintf(
            '<fg=yellow>Slow Queries:</fg=yellow> %d (%s%%)',
            $stats['slow_queries'],
            $stats['slow_query_percentage'] ?? 0
        ));

        // Color-code slow query percentage
        $slowPercentage = $stats['slow_query_percentage'] ?? 0;
        if ($slowPercentage > 10) {
            $this->line('<fg=red>⚠️ High percentage of slow queries detected!</fg=red>');
        } elseif ($slowPercentage > 5) {
            $this->line('<fg=yellow>⚠️ Elevated slow query percentage</fg=yellow>');
        } else {
            $this->line('<fg=green>✅ Slow query percentage is acceptable</fg=green>');
        }
    }

    /**
     * Display query type breakdown.
     */
    private function displayQueryTypeBreakdown(array $stats): void
    {
        if (empty($stats['by_type'])) {
            return;
        }

        $this->newLine();
        $this->line('<fg=cyan>📈 Query Type Breakdown</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        foreach ($stats['by_type'] as $type => $data) {
            $avgTime = $data['count'] > 0 ? round($data['total_time'] / $data['count'], 2) : 0;
            $percentage = round(($data['count'] / $stats['total_queries']) * 100, 1);
            
            $this->line(sprintf(
                '<fg=yellow>%s:</fg=yellow> %d queries (%s%%) - %s ms avg',
                $type,
                $data['count'],
                $percentage,
                $avgTime
            ));
        }
    }

    /**
     * Display slow queries.
     */
    private function displaySlowQueries(DatabaseQueryAnalyzer $analyzer): void
    {
        $slowQueries = $analyzer->getSlowQueries();
        
        if (empty($slowQueries)) {
            return;
        }

        $this->newLine();
        $this->line('<fg=cyan>🐌 Recent Slow Queries</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $recentSlowQueries = array_slice($slowQueries, -5); // Last 5 slow queries
        
        foreach ($recentSlowQueries as $query) {
            $timeColor = $query['time_ms'] > 5000 ? 'red' : 'yellow';
            
            $this->line(sprintf(
                '<fg=%s>⏱️ %s ms</fg=%s> - %s',
                $timeColor,
                $query['time_ms'],
                $timeColor,
                substr($query['sql'], 0, 80) . '...'
            ));
        }

        if (count($slowQueries) > 5) {
            $this->line(sprintf(
                '<fg=gray>... and %d more slow queries</fg=gray>',
                count($slowQueries) - 5
            ));
        }
    }

    /**
     * Display historical data.
     */
    private function displayHistoricalData(DatabaseQueryAnalyzer $analyzer, int $hours): void
    {
        $this->newLine();
        $this->line('<fg=cyan>📊 Historical Performance Data</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $historicalData = $analyzer->getHistoricalStatistics($hours);
        
        if (empty($historicalData)) {
            $this->line('No historical data available');
            return;
        }

        $this->line(sprintf(
            '<fg=yellow>Data Points:</fg=yellow> %d hours',
            count($historicalData)
        ));

        // Show summary statistics
        $totalQueries = array_sum(array_column($historicalData, 'total_queries'));
        $avgResponseTime = 0;
        $totalSlowQueries = array_sum(array_column($historicalData, 'slow_queries'));

        if (count($historicalData) > 0) {
            $avgResponseTime = array_sum(array_column($historicalData, 'average_time')) / count($historicalData);
        }

        $this->line(sprintf(
            '<fg=yellow>Total Queries:</fg=yellow> %d',
            $totalQueries
        ));

        $this->line(sprintf(
            '<fg=yellow>Average Response Time:</fg=yellow> %s ms',
            round($avgResponseTime, 2)
        ));

        $this->line(sprintf(
            '<fg=yellow>Total Slow Queries:</fg=yellow> %d',
            $totalSlowQueries
        ));
    }

    /**
     * Display performance trends.
     */
    private function displayPerformanceTrends(DatabaseQueryAnalyzer $analyzer, int $hours): void
    {
        $this->newLine();
        $this->line('<fg=cyan>📈 Performance Trends</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $historicalData = $analyzer->getHistoricalStatistics($hours);
        
        if (count($historicalData) < 2) {
            $this->line('Insufficient data for trend analysis');
            return;
        }

        // Analyze trends
        $dataPoints = array_values($historicalData);
        $recent = array_slice($dataPoints, -6); // Last 6 hours
        $older = array_slice($dataPoints, -12, 6); // 6 hours before that

        if (empty($older)) {
            $this->line('Insufficient data for trend comparison');
            return;
        }

        // Calculate averages
        $recentAvgTime = array_sum(array_column($recent, 'average_time')) / count($recent);
        $olderAvgTime = array_sum(array_column($older, 'average_time')) / count($older);

        $recentAvgQueries = array_sum(array_column($recent, 'total_queries')) / count($recent);
        $olderAvgQueries = array_sum(array_column($older, 'total_queries')) / count($older);

        // Calculate changes
        $timeChange = (($recentAvgTime - $olderAvgTime) / $olderAvgTime) * 100;
        $queryChange = (($recentAvgQueries - $olderAvgQueries) / $olderAvgQueries) * 100;

        // Display trends
        $this->displayTrend('Response Time', $timeChange, $recentAvgTime, $olderAvgTime, 'ms');
        $this->displayTrend('Query Volume', $queryChange, $recentAvgQueries, $olderAvgQueries, 'queries/hour');
    }

    /**
     * Display a trend analysis.
     */
    private function displayTrend(string $metric, float $change, float $recent, float $older, string $unit): void
    {
        $trendIcon = '→';
        $trendColor = 'white';

        if ($change > 10) {
            $trendIcon = '↗️';
            $trendColor = 'red';
        } elseif ($change < -10) {
            $trendIcon = '↘️';
            $trendColor = 'green';
        }

        $this->line(sprintf(
            '<fg=yellow>%s:</fg=yellow> <fg=%s>%s %+.1f%%</fg=%s> (%s %s → %s %s)',
            $metric,
            $trendColor,
            $trendIcon,
            $change,
            $trendColor,
            round($older, 2),
            $unit,
            round($recent, 2),
            $unit
        ));
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