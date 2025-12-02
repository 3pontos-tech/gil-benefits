<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Monitoring\MemoryOptimizationService;
use Illuminate\Console\Command;

class OptimizeMemoryUsageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:optimize-memory 
                            {--analyze : Analyze memory usage without optimization}
                            {--optimize : Perform memory optimization}
                            {--report : Generate detailed memory report}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze and optimize application memory usage';

    /**
     * Execute the console command.
     */
    public function handle(MemoryOptimizationService $memoryService): int
    {
        try {
            if ($this->option('optimize')) {
                return $this->performOptimization($memoryService);
            }

            if ($this->option('report')) {
                return $this->generateDetailedReport($memoryService);
            }

            // Default: analyze memory usage
            return $this->analyzeMemoryUsage($memoryService);

        } catch (\Exception $e) {
            $this->error('❌ Memory optimization failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Analyze memory usage.
     */
    private function analyzeMemoryUsage(MemoryOptimizationService $memoryService): int
    {
        $this->info('🧠 Analyzing memory usage...');
        
        $analysis = $memoryService->analyzeMemoryUsage();
        
        $this->displayCurrentMetrics($analysis['current_metrics']);
        $this->displayTrendAnalysis($analysis['trend_analysis']);
        $this->displayRecommendations($analysis['recommendations']);
        $this->displayOptimizationOpportunities($analysis['optimization_opportunities']);
        $this->displayMemoryLeakAnalysis($analysis['memory_leaks']);
        
        return Command::SUCCESS;
    }

    /**
     * Perform memory optimization.
     */
    private function performOptimization(MemoryOptimizationService $memoryService): int
    {
        $this->info('🚀 Performing memory optimization...');
        
        if (!$this->confirm('This will clear caches and run garbage collection. Continue?')) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }
        
        $result = $memoryService->performOptimization();
        
        $this->displayOptimizationResults($result);
        
        return $result['success'] ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Generate detailed report.
     */
    private function generateDetailedReport(MemoryOptimizationService $memoryService): int
    {
        $this->info('📊 Generating detailed memory report...');
        
        $analysis = $memoryService->analyzeMemoryUsage();
        
        // Display comprehensive analysis
        $this->analyzeMemoryUsage($memoryService);
        
        // Export to file
        $filename = 'memory_analysis_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('logs/' . $filename);
        
        file_put_contents($filepath, json_encode($analysis, JSON_PRETTY_PRINT));
        
        $this->info("✅ Detailed report exported to: {$filepath}");
        $this->line("📁 File size: " . $this->formatBytes(filesize($filepath)));
        
        return Command::SUCCESS;
    }

    /**
     * Display current memory metrics.
     */
    private function displayCurrentMetrics(array $metrics): void
    {
        $this->newLine();
        $this->line('<fg=cyan>🧠 Current Memory Metrics</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $usageColor = $this->getMemoryUsageColor($metrics['usage_percentage']);
        
        $this->line(sprintf(
            '<fg=yellow>Current Usage:</fg=yellow> <fg=%s>%s MB (%s%%)</fg=%s>',
            $usageColor,
            $metrics['current_usage_mb'],
            $metrics['usage_percentage'],
            $usageColor
        ));

        $this->line(sprintf(
            '<fg=yellow>Peak Usage:</fg=yellow> %s MB (%s%%)',
            $metrics['peak_usage_mb'],
            $metrics['peak_percentage']
        ));

        $this->line(sprintf(
            '<fg=yellow>Memory Limit:</fg=yellow> %s MB',
            $metrics['limit_mb']
        ));

        $this->line(sprintf(
            '<fg=yellow>Available:</fg=yellow> %s MB',
            $metrics['available_mb']
        ));

        $this->line(sprintf(
            '<fg=yellow>PHP Version:</fg=yellow> %s',
            $metrics['php_version']
        ));

        $opcacheStatus = $metrics['opcache_enabled'] ? '<fg=green>Enabled</fg=green>' : '<fg=red>Disabled</fg=red>';
        $this->line(sprintf(
            '<fg=yellow>OPcache:</fg=yellow> %s',
            $opcacheStatus
        ));
    }

    /**
     * Display trend analysis.
     */
    private function displayTrendAnalysis(array $trends): void
    {
        $this->newLine();
        $this->line('<fg=cyan>📈 Memory Usage Trends</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($trends['trend'] === 'insufficient_data') {
            $this->line('<fg=yellow>⚠️ Insufficient data for trend analysis</fg=yellow>');
            return;
        }

        $trendIcon = match ($trends['trend']) {
            'increasing' => '📈',
            'decreasing' => '📉',
            default => '➡️',
        };

        $trendColor = match ($trends['trend']) {
            'increasing' => 'red',
            'decreasing' => 'green',
            default => 'yellow',
        };

        $this->line(sprintf(
            '<fg=yellow>Trend:</fg=yellow> <fg=%s>%s %s (%+.1f%%)</fg=%s>',
            $trendColor,
            $trendIcon,
            ucfirst($trends['trend']),
            $trends['change_percentage'],
            $trendColor
        ));

        $this->line(sprintf(
            '<fg=yellow>Stability:</fg=yellow> %s (CV: %s%%)',
            ucfirst($trends['stability']),
            $trends['coefficient_of_variation']
        ));

        $this->line(sprintf(
            '<fg=yellow>Recent Average:</fg=yellow> %s%%',
            $trends['recent_average']
        ));

        $this->line(sprintf(
            '<fg=yellow>Prediction:</fg=yellow> %s',
            $trends['prediction']
        ));
    }

    /**
     * Display recommendations.
     */
    private function displayRecommendations(array $recommendations): void
    {
        $this->newLine();
        $this->line('<fg=cyan>💡 Optimization Recommendations</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        foreach ($recommendations as $rec) {
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
                $rec['title'],
                $priorityColor
            ));

            $this->line('📝 ' . $rec['description']);
            $this->line('🏷️ Category: ' . ucfirst(str_replace('_', ' ', $rec['category'])));
            $this->line('💥 Impact: ' . ucfirst($rec['impact']));

            if (!empty($rec['actions'])) {
                $this->line('🔧 Actions:');
                foreach ($rec['actions'] as $action) {
                    $this->line('  • ' . $action);
                }
            }
        }
    }

    /**
     * Display optimization opportunities.
     */
    private function displayOptimizationOpportunities(array $opportunities): void
    {
        if (empty($opportunities)) {
            return;
        }

        $this->newLine();
        $this->line('<fg=cyan>🎯 Optimization Opportunities</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        foreach ($opportunities as $opp) {
            $riskColor = match ($opp['risk']) {
                'high' => 'red',
                'medium' => 'yellow',
                'low' => 'green',
                default => 'white',
            };

            $this->newLine();
            $this->line(sprintf(
                '<fg=yellow>%s</fg=yellow> <fg=%s>(Risk: %s)</fg=%s>',
                $opp['title'],
                $riskColor,
                ucfirst($opp['risk']),
                $riskColor
            ));

            $this->line('📝 ' . $opp['description']);

            if (isset($opp['potential_savings'])) {
                $this->line('💰 Potential savings: ' . $opp['potential_savings']);
            }

            if (isset($opp['average_usage'])) {
                $this->line('📊 Average usage: ' . $opp['average_usage']);
            }
        }
    }

    /**
     * Display memory leak analysis.
     */
    private function displayMemoryLeakAnalysis(array $leakAnalysis): void
    {
        $this->newLine();
        $this->line('<fg=cyan>🔍 Memory Leak Analysis</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if ($leakAnalysis['status'] === 'insufficient_data') {
            $this->line('<fg=yellow>⚠️ ' . $leakAnalysis['analysis'] . '</fg=yellow>');
            return;
        }

        if ($leakAnalysis['leaks_detected']) {
            $severityColor = match ($leakAnalysis['severity']) {
                'high' => 'red',
                'medium' => 'yellow',
                'low' => 'green',
                default => 'white',
            };

            $this->line(sprintf(
                '<fg=%s>🚨 Memory leaks detected (Severity: %s)</fg=%s>',
                $severityColor,
                ucfirst($leakAnalysis['severity']),
                $severityColor
            ));
        } else {
            $this->line('<fg=green>✅ No memory leaks detected</fg=green>');
        }

        $this->line('📝 ' . $leakAnalysis['analysis']);

        if (isset($leakAnalysis['increasing_trend_percentage'])) {
            $this->line(sprintf(
                '📈 Increasing trend: %s%% of time periods',
                $leakAnalysis['increasing_trend_percentage']
            ));
        }

        if (isset($leakAnalysis['usage_range'])) {
            $this->line(sprintf(
                '📊 Usage range: %s%% (Min: %s%%, Max: %s%%)',
                $leakAnalysis['usage_range'],
                $leakAnalysis['min_usage'],
                $leakAnalysis['max_usage']
            ));
        }
    }

    /**
     * Display optimization results.
     */
    private function displayOptimizationResults(array $result): void
    {
        $this->newLine();
        $this->line('<fg=cyan>🚀 Optimization Results</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $successIcon = $result['success'] ? '✅' : '❌';
        $successColor = $result['success'] ? 'green' : 'red';

        $this->line(sprintf(
            '<fg=%s>%s Optimization %s</fg=%s>',
            $successColor,
            $successIcon,
            $result['success'] ? 'Successful' : 'Failed',
            $successColor
        ));

        $this->line(sprintf(
            '<fg=yellow>Memory Freed:</fg=yellow> %s MB',
            $result['memory_freed_mb']
        ));

        $this->line(sprintf(
            '<fg=yellow>Before:</fg=yellow> %s MB (%s%%)',
            $result['before_optimization']['current_usage_mb'],
            $result['before_optimization']['usage_percentage']
        ));

        $this->line(sprintf(
            '<fg=yellow>After:</fg=yellow> %s MB (%s%%)',
            $result['after_optimization']['current_usage_mb'],
            $result['after_optimization']['usage_percentage']
        ));

        $this->newLine();
        $this->line('<fg=yellow>Actions Performed:</fg=yellow>');
        foreach ($result['actions_performed'] as $action) {
            $statusIcon = match ($action['status']) {
                'success' => '✅',
                'failed' => '❌',
                'skipped' => '⏭️',
                default => '❓',
            };

            $this->line(sprintf(
                '  %s %s: %s',
                $statusIcon,
                ucfirst(str_replace('_', ' ', $action['action'])),
                $action['description']
            ));
        }
    }

    /**
     * Get color for memory usage percentage.
     */
    private function getMemoryUsageColor(float $percentage): string
    {
        if ($percentage > 90) return 'red';
        if ($percentage > 80) return 'yellow';
        return 'green';
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