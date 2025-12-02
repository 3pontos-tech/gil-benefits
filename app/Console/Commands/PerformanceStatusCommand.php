<?php

namespace App\Console\Commands;

use App\Services\PerformanceOptimizationService;
use Illuminate\Console\Command;

class PerformanceStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:status 
                            {--recommendations : Show optimization recommendations}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Display current performance optimization status';

    /**
     * Execute the console command.
     */
    public function handle(PerformanceOptimizationService $optimizationService): int
    {
        try {
            $status = $optimizationService->getPerformanceStatus();

            if ($this->option('json')) {
                $this->line(json_encode($status, JSON_PRETTY_PRINT));
                return Command::SUCCESS;
            }

            $this->displayStatus($status);

            if ($this->option('recommendations')) {
                $this->displayRecommendations($optimizationService);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Failed to get performance status: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Display performance status.
     */
    private function displayStatus(array $status): void
    {
        $this->info('📊 Performance Optimization Status');
        $this->line('');

        // Environment info
        $this->info('🌍 Environment');
        $this->line("  Environment: {$status['environment']}");
        $this->line('');

        // Cache status
        $this->info('💾 Cache Status');
        if (isset($status['cache_stats']['driver'])) {
            $this->line("  Driver: {$status['cache_stats']['driver']}");
            
            if (isset($status['cache_stats']['memory_usage'])) {
                $this->line("  Memory Usage: {$status['cache_stats']['memory_usage']}");
            }
            
            if (isset($status['cache_stats']['keyspace_hits'])) {
                $hits = $status['cache_stats']['keyspace_hits'];
                $misses = $status['cache_stats']['keyspace_misses'];
                $total = $hits + $misses;
                $hitRate = $total > 0 ? round(($hits / $total) * 100, 2) : 0;
                $this->line("  Hit Rate: {$hitRate}% ({$hits} hits, {$misses} misses)");
            }
        }
        $this->line('');

        // Optimization status
        $this->info('⚡ Laravel Optimizations');
        $optimizations = $status['optimization_status'];
        
        $this->displayOptimizationItem('Routes Cached', $optimizations['routes_cached']);
        $this->displayOptimizationItem('Config Cached', $optimizations['config_cached']);
        $this->displayOptimizationItem('Events Cached', $optimizations['events_cached']);
        $this->displayOptimizationItem('Views Cached', $optimizations['views_cached']);
        $this->line('');

        // Asset status
        $this->info('🎨 Asset Status');
        $assetStatus = $status['asset_status'];
        
        $this->displayOptimizationItem('Build Exists', $assetStatus['build_exists']);
        $this->displayOptimizationItem('Manifest Exists', $assetStatus['manifest_exists']);
        
        if (isset($assetStatus['build_stats']['total_files'])) {
            $stats = $assetStatus['build_stats'];
            $this->line("  Total Files: {$stats['total_files']}");
            $this->line("  Total Size: " . $this->formatBytes($stats['total_size']));
            $this->line("  CSS Files: {$stats['css_files']} (" . $this->formatBytes($stats['css_size']) . ")");
            $this->line("  JS Files: {$stats['js_files']} (" . $this->formatBytes($stats['js_size']) . ")");
        }
        $this->line('');

        // Queue status
        $this->info('📋 Queue Status');
        $queueStatus = $status['queue_status'];
        $this->line("  Connection: {$queueStatus['default_connection']}");
        $this->displayOptimizationItem('Status', $queueStatus['status'] === 'active');
        $this->line('');

        // Last optimization
        if ($status['last_optimization']) {
            $this->info('🕒 Last Optimization');
            $this->line("  Time: {$status['last_optimization']}");
        }
    }

    /**
     * Display optimization item with status icon.
     */
    private function displayOptimizationItem(string $name, bool $status): void
    {
        $icon = $status ? '✅' : '❌';
        $this->line("  {$icon} {$name}");
    }

    /**
     * Display optimization recommendations.
     */
    private function displayRecommendations(PerformanceOptimizationService $optimizationService): void
    {
        $recommendations = $optimizationService->getOptimizationRecommendations();

        if (empty($recommendations)) {
            $this->info('🎉 No optimization recommendations - everything looks good!');
            return;
        }

        $this->line('');
        $this->info('💡 Optimization Recommendations');
        $this->line('');

        foreach ($recommendations as $recommendation) {
            $priority = strtoupper($recommendation['priority']);
            $priorityIcon = match ($recommendation['priority']) {
                'high' => '🔴',
                'medium' => '🟡',
                'low' => '🟢',
                default => '⚪',
            };

            $this->line("{$priorityIcon} [{$priority}] {$recommendation['message']}");
            $this->line("   Action: {$recommendation['action']}");
            $this->line('');
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
