<?php

namespace App\Console\Commands;

use App\Services\PerformanceOptimizationService;
use Illuminate\Console\Command;

class OptimizePerformanceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:optimize 
                            {--async : Run optimization asynchronously using jobs}
                            {--production : Optimize for production environment}
                            {--development : Optimize for development environment}
                            {--clear : Clear all caches instead of optimizing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize application performance with caching and asset optimization';

    /**
     * Execute the console command.
     */
    public function handle(PerformanceOptimizationService $optimizationService): int
    {
        $this->info('🚀 Starting performance optimization...');

        try {
            if ($this->option('clear')) {
                return $this->clearCaches($optimizationService);
            }

            if ($this->option('production')) {
                return $this->optimizeForProduction($optimizationService);
            }

            if ($this->option('development')) {
                return $this->optimizeForDevelopment($optimizationService);
            }

            // Default: full optimization
            return $this->runFullOptimization($optimizationService);

        } catch (\Exception $e) {
            $this->error('❌ Optimization failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Clear all caches.
     */
    private function clearCaches(PerformanceOptimizationService $optimizationService): int
    {
        $this->info('🧹 Clearing all caches...');

        $async = $this->option('async');
        $result = $optimizationService->clearAllCaches($async);

        if ($result['status'] === 'completed') {
            $this->info('✅ All caches cleared successfully');
            return Command::SUCCESS;
        }

        if ($result['status'] === 'queued') {
            $this->info('📋 Cache clearing job queued: ' . $result['job_id']);
            return Command::SUCCESS;
        }

        $this->error('❌ Cache clearing failed: ' . ($result['error'] ?? 'Unknown error'));
        return Command::FAILURE;
    }

    /**
     * Optimize for production.
     */
    private function optimizeForProduction(PerformanceOptimizationService $optimizationService): int
    {
        $this->info('🏭 Optimizing for production environment...');

        $async = $this->option('async');
        $result = $optimizationService->optimizeForProduction($async);

        if ($result['status'] === 'completed') {
            $this->info('✅ Production optimization completed successfully');
            $this->displayResults($result);
            return Command::SUCCESS;
        }

        if ($result['status'] === 'queued') {
            $this->info('📋 Production optimization job queued: ' . $result['job_id']);
            return Command::SUCCESS;
        }

        $this->error('❌ Production optimization failed: ' . ($result['error'] ?? 'Unknown error'));
        return Command::FAILURE;
    }

    /**
     * Optimize for development.
     */
    private function optimizeForDevelopment(PerformanceOptimizationService $optimizationService): int
    {
        $this->info('🛠️ Optimizing for development environment...');

        $result = $optimizationService->optimizeForDevelopment();

        if ($result['status'] === 'completed') {
            $this->info('✅ Development optimization completed successfully');
            return Command::SUCCESS;
        }

        $this->error('❌ Development optimization failed: ' . ($result['error'] ?? 'Unknown error'));
        return Command::FAILURE;
    }

    /**
     * Run full optimization.
     */
    private function runFullOptimization(PerformanceOptimizationService $optimizationService): int
    {
        $this->info('⚡ Running full application optimization...');

        $async = $this->option('async');
        $result = $optimizationService->optimizeApplication($async);

        if ($result['status'] === 'completed') {
            $this->info('✅ Full optimization completed successfully');
            $this->displayResults($result);
            return Command::SUCCESS;
        }

        if ($result['status'] === 'queued') {
            $this->info('📋 Optimization jobs queued:');
            foreach ($result['job_ids'] as $type => $jobId) {
                $this->line("  - {$type}: {$jobId}");
            }
            return Command::SUCCESS;
        }

        $this->error('❌ Full optimization failed: ' . ($result['error'] ?? 'Unknown error'));
        return Command::FAILURE;
    }

    /**
     * Display optimization results.
     */
    private function displayResults(array $result): void
    {
        if (isset($result['laravel'])) {
            $this->info('📦 Laravel Optimization:');
            foreach ($result['laravel'] as $type => $status) {
                $icon = $status ? '✅' : '❌';
                $this->line("  {$icon} {$type}");
            }
        }

        if (isset($result['assets'])) {
            $this->info('🎨 Asset Optimization:');
            $this->line('  ✅ Assets built successfully');
        }

        if (isset($result['cache_warmup'])) {
            $this->info('🔥 Cache Warmup:');
            $this->line('  ✅ Application cache warmed up');
        }
    }
}
