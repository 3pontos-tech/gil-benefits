<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Monitoring\PerformanceMetricsCollector;
use App\Services\Monitoring\SystemMonitor;
use Illuminate\Console\Command;

class MonitoringCollectMetricsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:collect-metrics 
                            {--store : Store metrics for historical analysis}
                            {--alert : Check for performance alerts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Collect application performance metrics and system health data';

    /**
     * Execute the console command.
     */
    public function handle(
        PerformanceMetricsCollector $metricsCollector,
        SystemMonitor $systemMonitor
    ): int {
        $this->info('🔍 Collecting performance metrics...');

        try {
            // Collect performance metrics
            $metrics = $metricsCollector->collectMetrics();
            
            $this->info('✅ Performance metrics collected successfully');
            
            // Display key metrics
            $this->displayMetricsSummary($metrics);

            // Check system health if requested
            if ($this->option('alert')) {
                $this->info('🏥 Checking system health...');
                $health = $systemMonitor->checkSystemHealth();
                $this->displayHealthSummary($health);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to collect metrics: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display metrics summary.
     */
    private function displayMetricsSummary(array $metrics): void
    {
        $this->newLine();
        $this->line('<fg=cyan>📊 Performance Metrics Summary</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Memory usage
        if (isset($metrics['memory'])) {
            $memory = $metrics['memory'];
            $this->line(sprintf(
                '<fg=yellow>Memory:</fg=yellow> %s MB / %s MB (%s%%)',
                $memory['current_usage_mb'],
                $memory['limit_mb'],
                $memory['usage_percentage']
            ));
        }

        // Database performance
        if (isset($metrics['database']['connection_time_ms'])) {
            $dbTime = $metrics['database']['connection_time_ms'];
            $status = $dbTime > 1000 ? '<fg=red>SLOW</fg=red>' : '<fg=green>OK</fg=green>';
            $this->line(sprintf(
                '<fg=yellow>Database:</fg=yellow> %s ms %s',
                $dbTime,
                $status
            ));
        }

        // Cache performance
        if (isset($metrics['cache']['response_time_ms'])) {
            $cacheTime = $metrics['cache']['response_time_ms'];
            $status = $cacheTime > 100 ? '<fg=red>SLOW</fg=red>' : '<fg=green>OK</fg=green>';
            $this->line(sprintf(
                '<fg=yellow>Cache:</fg=yellow> %s ms %s',
                $cacheTime,
                $status
            ));
        }

        // Response times
        if (isset($metrics['response_times']) && $metrics['response_times']['sample_size'] > 0) {
            $rt = $metrics['response_times'];
            $this->line(sprintf(
                '<fg=yellow>Response Times:</fg=yellow> Avg: %s ms, P95: %s ms, P99: %s ms',
                $rt['average_ms'] ?? 'N/A',
                $rt['p95_ms'] ?? 'N/A',
                $rt['p99_ms'] ?? 'N/A'
            ));
        }

        $this->newLine();
    }

    /**
     * Display health summary.
     */
    private function displayHealthSummary(array $health): void
    {
        $this->line('<fg=cyan>🏥 System Health Summary</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $statusColor = match ($health['status']) {
            'healthy' => 'green',
            'degraded' => 'yellow',
            'critical' => 'red',
            default => 'white',
        };

        $this->line(sprintf(
            '<fg=yellow>Overall Status:</fg=yellow> <fg=%s>%s</fg=%s>',
            $statusColor,
            strtoupper($health['status']),
            $statusColor
        ));

        foreach ($health['checks'] as $component => $result) {
            $componentStatus = $result['status'] ?? 'unknown';
            $componentColor = match ($componentStatus) {
                'healthy' => 'green',
                'degraded' => 'yellow',
                'critical' => 'red',
                default => 'white',
            };

            $this->line(sprintf(
                '<fg=yellow>%s:</fg=yellow> <fg=%s>%s</fg=%s>',
                ucfirst($component),
                $componentColor,
                strtoupper($componentStatus),
                $componentColor
            ));

            // Show issues if any
            if (!empty($result['issues'])) {
                foreach ($result['issues'] as $issue) {
                    $this->line('  <fg=red>⚠</fg=red> ' . $issue);
                }
            }
        }

        $this->newLine();
    }
}
