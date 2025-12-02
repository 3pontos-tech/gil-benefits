<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Monitoring\ErrorRateMonitoringService;
use Illuminate\Console\Command;

class MonitorErrorRatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitoring:error-rates 
                            {--statistics : Show error rate statistics}
                            {--patterns : Analyze error patterns}
                            {--export : Export error analysis to file}
                            {--hours=24 : Hours of data to analyze}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Monitor and analyze application error rates';

    /**
     * Execute the console command.
     */
    public function handle(ErrorRateMonitoringService $errorService): int
    {
        try {
            $hours = (int) $this->option('hours');

            if ($this->option('export')) {
                return $this->exportErrorAnalysis($errorService, $hours);
            }

            if ($this->option('patterns')) {
                return $this->analyzeErrorPatterns($errorService, $hours);
            }

            // Default: show statistics
            return $this->showErrorStatistics($errorService, $hours);

        } catch (\Exception $e) {
            $this->error('❌ Error rate monitoring failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Show error rate statistics.
     */
    private function showErrorStatistics(ErrorRateMonitoringService $errorService, int $hours): int
    {
        $this->info("📊 Error Rate Statistics (Last {$hours} hours)");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $statistics = $errorService->getErrorRateStatistics($hours);

        // Display overall statistics
        $this->displayOverallStatistics($statistics['overall']);

        // Display trends
        $this->displayTrends($statistics['trends']);

        // Display hourly breakdown
        $this->displayHourlyBreakdown($statistics['by_hour']);

        // Display error types
        $this->displayErrorTypes($statistics['by_type']);

        // Display severity breakdown
        $this->displaySeverityBreakdown($statistics['by_severity']);

        // Display top errors
        $this->displayTopErrors($statistics['top_errors']);

        return Command::SUCCESS;
    }

    /**
     * Analyze error patterns.
     */
    private function analyzeErrorPatterns(ErrorRateMonitoringService $errorService, int $hours): int
    {
        $this->info("🔍 Error Pattern Analysis (Last {$hours} hours)");
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $patterns = $errorService->analyzeErrorPatterns($hours);

        // Display error spikes
        $this->displayErrorSpikes($patterns['error_spikes']);

        // Display recurring errors
        $this->displayRecurringErrors($patterns['recurring_errors']);

        // Display user impact
        $this->displayUserImpact($patterns['user_impact']);

        // Display time patterns
        $this->displayTimePatterns($patterns['time_patterns']);

        // Display recommendations
        $this->displayRecommendations($patterns['recommendations']);

        return Command::SUCCESS;
    }

    /**
     * Export error analysis to file.
     */
    private function exportErrorAnalysis(ErrorRateMonitoringService $errorService, int $hours): int
    {
        $this->info('📤 Exporting error rate analysis...');

        $statistics = $errorService->getErrorRateStatistics($hours);
        $patterns = $errorService->analyzeErrorPatterns($hours);

        $report = [
            'generated_at' => now()->toISOString(),
            'analysis_period_hours' => $hours,
            'statistics' => $statistics,
            'patterns' => $patterns,
        ];

        $filename = 'error_rate_analysis_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('logs/' . $filename);

        file_put_contents($filepath, json_encode($report, JSON_PRETTY_PRINT));

        $this->info("✅ Error rate analysis exported to: {$filepath}");
        $this->line("📁 File size: " . $this->formatBytes(filesize($filepath)));

        return Command::SUCCESS;
    }

    /**
     * Display overall statistics.
     */
    private function displayOverallStatistics(array $overall): void
    {
        $this->newLine();
        $this->line('<fg=cyan>📈 Overall Error Rate</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $statusColor = match ($overall['status']) {
            'critical' => 'red',
            'warning' => 'yellow',
            'normal' => 'green',
            default => 'white',
        };

        $this->line(sprintf(
            '<fg=yellow>Error Rate:</fg=yellow> <fg=%s>%s%% (%s)</fg=%s>',
            $statusColor,
            $overall['error_rate_percentage'],
            ucfirst($overall['status']),
            $statusColor
        ));

        $this->line(sprintf(
            '<fg=yellow>Total Errors:</fg=yellow> %s',
            number_format($overall['total_errors'])
        ));

        $this->line(sprintf(
            '<fg=yellow>Total Requests:</fg=yellow> %s',
            number_format($overall['total_requests'])
        ));
    }

    /**
     * Display trends.
     */
    private function displayTrends(array $trends): void
    {
        $this->newLine();
        $this->line('<fg=cyan>📊 Error Rate Trends</fg=cyan>');
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
            '<fg=yellow>Recent Average:</fg=yellow> %s%%',
            $trends['recent_average']
        ));

        $this->line(sprintf(
            '<fg=yellow>Previous Average:</fg=yellow> %s%%',
            $trends['older_average']
        ));

        $this->line(sprintf(
            '<fg=yellow>Prediction:</fg=yellow> %s',
            $trends['prediction']
        ));
    }

    /**
     * Display hourly breakdown.
     */
    private function displayHourlyBreakdown(array $hourlyData): void
    {
        $this->newLine();
        $this->line('<fg=cyan>⏰ Hourly Error Rates</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        // Show last 12 hours
        $recentHours = array_slice($hourlyData, -12);

        foreach ($recentHours as $hour) {
            $statusColor = match ($hour['status']) {
                'critical' => 'red',
                'warning' => 'yellow',
                'normal' => 'green',
                default => 'white',
            };

            $this->line(sprintf(
                '<fg=yellow>%s:</fg=yellow> <fg=%s>%s%% (%d errors / %d requests)</fg=%s>',
                substr($hour['hour'], -5), // Show only HH:MM
                $statusColor,
                $hour['error_rate_percentage'],
                $hour['errors'],
                $hour['requests'],
                $statusColor
            ));
        }
    }

    /**
     * Display error types.
     */
    private function displayErrorTypes(array $errorTypes): void
    {
        if (empty($errorTypes)) {
            return;
        }

        $this->newLine();
        $this->line('<fg=cyan>🏷️ Error Types</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $topTypes = array_slice($errorTypes, 0, 10);

        foreach ($topTypes as $type) {
            $this->line(sprintf(
                '<fg=yellow>%s:</fg=yellow> %d occurrences (%s%%)',
                $type['error_type'],
                $type['count'],
                $type['error_rate_percentage']
            ));
        }
    }

    /**
     * Display severity breakdown.
     */
    private function displaySeverityBreakdown(array $severities): void
    {
        $this->newLine();
        $this->line('<fg=cyan>⚠️ Error Severity Breakdown</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        foreach ($severities as $severity) {
            if ($severity['count'] === 0) {
                continue;
            }

            $severityColor = match ($severity['severity']) {
                'critical' => 'red',
                'error' => 'red',
                'warning' => 'yellow',
                'info' => 'green',
                default => 'white',
            };

            $this->line(sprintf(
                '<fg=%s>%s:</fg=%s> %d errors (%s%%)',
                $severityColor,
                ucfirst($severity['severity']),
                $severityColor,
                $severity['count'],
                $severity['error_rate_percentage']
            ));
        }
    }

    /**
     * Display top errors.
     */
    private function displayTopErrors(array $topErrors): void
    {
        if (empty($topErrors)) {
            return;
        }

        $this->newLine();
        $this->line('<fg=cyan>🔥 Top Errors</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        foreach (array_slice($topErrors, 0, 5) as $index => $error) {
            $this->line(sprintf(
                '<fg=yellow>%d. %s</fg=yellow> (%d occurrences)',
                $index + 1,
                $error['error_type'],
                $error['count']
            ));

            $this->line('   ' . substr($error['message'], 0, 80) . '...');

            $primarySeverity = array_keys($error['severities'], max($error['severities']))[0];
            $severityColor = match ($primarySeverity) {
                'critical' => 'red',
                'error' => 'red',
                'warning' => 'yellow',
                default => 'green',
            };

            $this->line(sprintf(
                '   <fg=%s>Primary severity: %s</fg=%s> | First: %s | Last: %s',
                $severityColor,
                $primarySeverity,
                $severityColor,
                substr($error['first_seen'], 11, 8),
                substr($error['last_seen'], 11, 8)
            ));

            $this->newLine();
        }
    }

    /**
     * Display error spikes.
     */
    private function displayErrorSpikes(array $spikes): void
    {
        $this->newLine();
        $this->line('<fg=cyan>🚨 Error Spikes</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if (empty($spikes)) {
            $this->line('<fg=green>✅ No error spikes detected</fg=green>');
            return;
        }

        foreach ($spikes as $spike) {
            $severityColor = $spike['severity'] === 'critical' ? 'red' : 'yellow';

            $this->line(sprintf(
                '<fg=%s>🚨 %s:</fg=%s> %s%% → %s%% (+%s%%)',
                $severityColor,
                substr($spike['hour'], -5),
                $severityColor,
                $spike['previous_rate'],
                $spike['error_rate'],
                $spike['increase_percentage']
            ));
        }
    }

    /**
     * Display recurring errors.
     */
    private function displayRecurringErrors(array $recurringErrors): void
    {
        $this->newLine();
        $this->line('<fg=cyan>🔄 Recurring Errors</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if (empty($recurringErrors)) {
            $this->line('<fg=green>✅ No significant recurring errors</fg=green>');
            return;
        }

        foreach (array_slice($recurringErrors, 0, 5) as $error) {
            $severityColor = match ($error['primary_severity']) {
                'critical' => 'red',
                'error' => 'red',
                'warning' => 'yellow',
                default => 'green',
            };

            $this->line(sprintf(
                '<fg=%s>%s</fg=%s> (%d times, %s/hour)',
                $severityColor,
                $error['error_type'],
                $severityColor,
                $error['count'],
                $error['frequency_per_hour']
            ));

            $this->line('   ' . $error['message']);
        }
    }

    /**
     * Display user impact.
     */
    private function displayUserImpact(array $userImpact): void
    {
        $this->newLine();
        $this->line('<fg=cyan>👥 User Impact Analysis</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $impactColor = match ($userImpact['severity']) {
            'critical' => 'red',
            'high' => 'red',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'white',
        };

        $this->line(sprintf(
            '<fg=yellow>Affected Users:</fg=yellow> <fg=%s>%d out of %d (%s%% - %s impact)</fg=%s>',
            $impactColor,
            $userImpact['affected_users'],
            $userImpact['total_users'],
            $userImpact['impact_percentage'],
            $userImpact['severity'],
            $impactColor
        ));
    }

    /**
     * Display time patterns.
     */
    private function displayTimePatterns(array $timePatterns): void
    {
        $this->newLine();
        $this->line('<fg=cyan>🕐 Time Patterns</fg=cyan>');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        if (!empty($timePatterns['peak_error_hours'])) {
            $this->line(sprintf(
                '<fg=yellow>Peak Error Hours:</fg=yellow> %s',
                implode(', ', array_map(function ($hour) {
                    return sprintf('%02d:00', $hour);
                }, $timePatterns['peak_error_hours']))
            ));
        }

        if (isset($timePatterns['pattern_analysis'])) {
            $this->line(sprintf(
                '<fg=yellow>Pattern:</fg=yellow> %s',
                $timePatterns['pattern_analysis']
            ));
        }
    }

    /**
     * Display recommendations.
     */
    private function displayRecommendations(array $recommendations): void
    {
        if (empty($recommendations)) {
            return;
        }

        $this->newLine();
        $this->line('<fg=cyan>💡 Recommendations</fg=cyan>');
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

            if (!empty($rec['actions'])) {
                $this->line('🔧 Actions:');
                foreach ($rec['actions'] as $action) {
                    $this->line('  • ' . $action);
                }
            }
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