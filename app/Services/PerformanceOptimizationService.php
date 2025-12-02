<?php

namespace App\Services;

use App\Jobs\CacheWarmupJob;
use App\Jobs\AssetOptimizationJob;
use App\Jobs\ProductionOptimizationJob;
use App\Jobs\CacheClearJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PerformanceOptimizationService
{
    private const CACHE_PREFIX = 'performance_optimization:';

    public function __construct(
        private readonly CacheService $cacheService,
        private readonly ViewCacheService $viewCacheService,
        private readonly RouteCacheService $routeCacheService,
        private readonly AssetOptimizationService $assetService
    ) {}

    /**
     * Run complete performance optimization.
     */
    public function optimizeApplication(bool $async = true): array
    {
        Log::info('Starting complete application optimization', ['async' => $async]);

        if ($async) {
            return $this->optimizeAsync();
        }

        return $this->optimizeSync();
    }

    /**
     * Run optimization asynchronously using jobs.
     */
    private function optimizeAsync(): array
    {
        $jobIds = [];

        // Queue production optimization job
        $productionJob = new ProductionOptimizationJob(true, true);
        $jobIds['production_optimization'] = Queue::push($productionJob);

        // Queue cache warmup job
        $cacheWarmupJob = new CacheWarmupJob(['application', 'views']);
        $jobIds['cache_warmup'] = Queue::push($cacheWarmupJob);

        // Queue asset optimization job
        $assetJob = new AssetOptimizationJob(['build', 'images', 'compress']);
        $jobIds['asset_optimization'] = Queue::push($assetJob);

        Log::info('Optimization jobs queued', ['job_ids' => $jobIds]);

        return [
            'status' => 'queued',
            'job_ids' => $jobIds,
            'message' => 'Optimization jobs have been queued for background processing',
        ];
    }

    /**
     * Run optimization synchronously.
     */
    private function optimizeSync(): array
    {
        $results = [];

        try {
            // Laravel optimization
            Log::info('Running Laravel optimization');
            $results['laravel'] = $this->routeCacheService->optimizeForProduction();

            // Asset optimization
            Log::info('Running asset optimization');
            $results['assets'] = $this->assetService->buildAssets();
            $results['image_optimization'] = $this->assetService->optimizeImages();
            $results['asset_compression'] = $this->assetService->compressAssets();

            // Cache warmup
            Log::info('Warming up caches');
            $this->cacheService->warmUpCache();
            $this->viewCacheService->warmUpViewCache();
            $results['cache_warmup'] = true;

            $results['status'] = 'completed';
            $results['message'] = 'All optimizations completed successfully';

            Log::info('Synchronous optimization completed', ['results' => $results]);

        } catch (\Exception $e) {
            Log::error('Synchronous optimization failed', ['error' => $e->getMessage()]);
            
            $results['status'] = 'failed';
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Optimize for development environment.
     */
    public function optimizeForDevelopment(): array
    {
        Log::info('Optimizing for development environment');

        $results = [];

        try {
            // Clear all optimization caches
            $results['clear_optimizations'] = $this->routeCacheService->clearAllOptimizations();

            // Warm up essential caches only
            $this->cacheService->warmUpCache();
            $results['cache_warmup'] = true;

            $results['status'] = 'completed';
            $results['message'] = 'Development optimization completed';

            Log::info('Development optimization completed');

        } catch (\Exception $e) {
            Log::error('Development optimization failed', ['error' => $e->getMessage()]);
            
            $results['status'] = 'failed';
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Optimize for production environment.
     */
    public function optimizeForProduction(bool $async = true): array
    {
        Log::info('Optimizing for production environment', ['async' => $async]);

        if ($async) {
            // Queue production optimization job
            $job = new ProductionOptimizationJob(true, true);
            $jobId = Queue::push($job);

            return [
                'status' => 'queued',
                'job_id' => $jobId,
                'message' => 'Production optimization job queued',
            ];
        }

        return $this->optimizeSync();
    }

    /**
     * Clear all performance caches.
     */
    public function clearAllCaches(bool $async = false): array
    {
        Log::info('Clearing all performance caches', ['async' => $async]);

        if ($async) {
            // Queue cache clear job
            $job = new CacheClearJob(['application', 'views']);
            $jobId = Queue::push($job);

            return [
                'status' => 'queued',
                'job_id' => $jobId,
                'message' => 'Cache clear job queued',
            ];
        }

        $results = [];

        try {
            // Clear application cache
            $this->cacheService->flush();
            $results['application_cache'] = true;

            // Clear view cache
            $this->viewCacheService->clearAllViewCache();
            $results['view_cache'] = true;

            // Clear Laravel optimizations
            $results['laravel_optimizations'] = $this->routeCacheService->clearAllOptimizations();

            $results['status'] = 'completed';
            $results['message'] = 'All caches cleared successfully';

            Log::info('All caches cleared successfully');

        } catch (\Exception $e) {
            Log::error('Cache clearing failed', ['error' => $e->getMessage()]);
            
            $results['status'] = 'failed';
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Invalidate cache for specific entity.
     */
    public function invalidateEntityCache(string $entityType, int $entityId, bool $async = false): array
    {
        Log::info("Invalidating cache for {$entityType}:{$entityId}", ['async' => $async]);

        if ($async) {
            // Queue cache clear job for specific entity
            $job = new CacheClearJob(['application', 'views'], $entityType, $entityId);
            $jobId = Queue::push($job);

            return [
                'status' => 'queued',
                'job_id' => $jobId,
                'message' => "Cache invalidation job queued for {$entityType}:{$entityId}",
            ];
        }

        try {
            // Clear entity-specific cache
            $this->cacheService->invalidateRelatedCache($entityType, $entityId);

            // Clear related view cache
            if ($entityType === 'user') {
                $this->viewCacheService->invalidateNavigationCache($entityId);
                $this->viewCacheService->invalidateWidgetCache($entityId);
            }

            Log::info("Cache invalidated for {$entityType}:{$entityId}");

            return [
                'status' => 'completed',
                'message' => "Cache invalidated for {$entityType}:{$entityId}",
            ];

        } catch (\Exception $e) {
            Log::error("Cache invalidation failed for {$entityType}:{$entityId}", ['error' => $e->getMessage()]);
            
            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get comprehensive performance status.
     */
    public function getPerformanceStatus(): array
    {
        return [
            'environment' => config('app.env'),
            'cache_stats' => $this->cacheService->getCacheStats(),
            'optimization_status' => $this->routeCacheService->getOptimizationStatus(),
            'asset_status' => $this->assetService->getOptimizationStatus(),
            'queue_status' => $this->getQueueStatus(),
            'last_optimization' => $this->getLastOptimizationTime(),
        ];
    }

    /**
     * Get queue status.
     */
    private function getQueueStatus(): array
    {
        try {
            // This would depend on your queue driver
            return [
                'default_connection' => config('queue.default'),
                'status' => 'active',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get last optimization time.
     */
    private function getLastOptimizationTime(): ?string
    {
        return Cache::get(self::CACHE_PREFIX . 'last_full_optimization');
    }

    /**
     * Mark full optimization as completed.
     */
    public function markOptimizationCompleted(): void
    {
        Cache::put(self::CACHE_PREFIX . 'last_full_optimization', now()->toISOString(), 86400);
        Log::info('Full optimization marked as completed');
    }

    /**
     * Schedule regular cache warmup.
     */
    public function scheduleRegularWarmup(): void
    {
        // Queue cache warmup job to run every hour
        $job = new CacheWarmupJob(['application', 'views']);
        $job->delay(now()->addHour());
        
        Queue::push($job);
        
        Log::info('Regular cache warmup scheduled');
    }

    /**
     * Schedule asset optimization.
     */
    public function scheduleAssetOptimization(): void
    {
        // Queue asset optimization job to run daily
        $job = new AssetOptimizationJob(['images', 'compress']);
        $job->delay(now()->addDay());
        
        Queue::push($job);
        
        Log::info('Asset optimization scheduled');
    }

    /**
     * Get optimization recommendations.
     */
    public function getOptimizationRecommendations(): array
    {
        $recommendations = [];
        
        $status = $this->getPerformanceStatus();
        
        // Check if running in production
        if ($status['environment'] !== 'production') {
            $recommendations[] = [
                'type' => 'environment',
                'priority' => 'high',
                'message' => 'Application is not running in production mode',
                'action' => 'Set APP_ENV=production in .env file',
            ];
        }
        
        // Check if optimizations are cached
        if (!$status['optimization_status']['routes_cached']) {
            $recommendations[] = [
                'type' => 'routes',
                'priority' => 'medium',
                'message' => 'Routes are not cached',
                'action' => 'Run route caching for better performance',
            ];
        }
        
        if (!$status['optimization_status']['config_cached']) {
            $recommendations[] = [
                'type' => 'config',
                'priority' => 'medium',
                'message' => 'Configuration is not cached',
                'action' => 'Run config caching for better performance',
            ];
        }
        
        // Check asset optimization
        if ($status['asset_status']['needs_optimization']) {
            $recommendations[] = [
                'type' => 'assets',
                'priority' => 'medium',
                'message' => 'Assets need optimization',
                'action' => 'Run asset optimization to improve load times',
            ];
        }
        
        return $recommendations;
    }
}