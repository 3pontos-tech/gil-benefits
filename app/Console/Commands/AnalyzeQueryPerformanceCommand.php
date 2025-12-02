<?php

namespace App\Console\Commands;

use App\Services\QueryOptimizationService;
use Illuminate\Console\Command;

class AnalyzeQueryPerformanceCommand extends Command
{
    protected $signature = 'db:analyze-performance 
                           {--threshold=100 : Slow query threshold in milliseconds}
                           {--duration=60 : Duration to monitor in seconds}
                           {--output= : Output file path for the report}';

    protected $description = 'Analyze database query performance and provide optimization suggestions';

    public function handle(): int
    {
        $threshold = (float) $this->option('threshold');
        $duration = (int) $this->option('duration');
        $outputFile = $this->option('output');

        $this->info('Starting query performance analysis...');
        $this->info("Slow query threshold: {$threshold}ms");
        $this->info("Monitoring duration: {$duration} seconds");

        $service = new QueryOptimizationService;
        $service->setSlowQueryThreshold($threshold);

        // Monitor for specified duration
        $this->info('Monitoring queries...');
        $progressBar = $this->output->createProgressBar($duration);
        $progressBar->start();

        for ($i = 0; $i < $duration; ++$i) {
            sleep(1);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        // Generate report
        $report = $service->generatePerformanceReport();

        $this->displayReport($report);

        // Save to file if specified
        if ($outputFile) {
            file_put_contents($outputFile, json_encode($report, JSON_PRETTY_PRINT));
            $this->info("Report saved to: {$outputFile}");
        }

        return Command::SUCCESS;
    }

    private function displayReport(array $report): void
    {
        $this->newLine();
        $this->info('=== Query Performance Report ===');
        $this->newLine();

        // Summary
        $this->info('Summary:');
        foreach ($report['summary'] as $key => $value) {
            $this->line("  {$key}: {$value}");
        }

        $this->newLine();

        // Slow queries
        if (! empty($report['slow_queries'])) {
            $this->warn('Slow Queries:');
            foreach ($report['slow_queries'] as $index => $query) {
                $this->line('  ' . ($index + 1) . ". {$query['sql']} ({$query['time']}ms)");
            }
            $this->newLine();
        }

        // Optimization suggestions
        if (! empty($report['optimization_suggestions'])) {
            $this->warn('Optimization Suggestions:');
            foreach ($report['optimization_suggestions'] as $index => $suggestion) {
                $this->line('  ' . ($index + 1) . ". [{$suggestion['type']}] {$suggestion['suggestion']}");
                $this->line("     Query: {$suggestion['query']}");
            }
        } else {
            $this->info('No optimization suggestions at this time.');
        }
    }
}
