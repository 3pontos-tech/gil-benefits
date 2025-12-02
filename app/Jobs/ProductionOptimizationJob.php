<?php

namespace App\Jobs;

use App\Services\RouteCacheService;
use App\Services\AssetOptimizationService;
use App\Services\CacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProductionOptimizationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 900; // 15 minutes
    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly bool $includeAssets = true,
        private readonly bool $includeCache = true
    ) {
        $this->onQueue('optimization');
    }

    /**
     * Execute the job.
     */
    public function handle(
        RouteCacheService $routeCacheService,
        AssetOptimizationService $assetService,
        CacheService $cacheService
    ): void {
        Log::info('Starting production optimization job');

        $results = [];

        try {
            // Optimize Laravel caches
            Log::info('Optimizing Laravel caches');
            $results['laravel_optimization'] = $routeCacheService->optimizeForProduction();

            // Build and optimize assets
            if ($this->includeAssets) {
                Log::info('Building and optimizing assets');
                $results['asset_build'] = $assetService->buildAssets();
                $results['asset_optimization'] = $assetService->optimizeImages();
                $results['asset_compression'] = $assetService->compressAssets();
            }

            // Warm up application cache
            if ($this->includeCache) {
                Log::info('Warming up application cache');
                $cacheService->warmUpCache();
                $results['cache_warmup'] = true;
            }

            // Mark optimization as completed
            $routeCacheService->markOptimizationCompleted();

            Log::info('Production optimization job completed successfully', ['results' => $results]);

        } catch (\Exception $e) {
            Log::error('Production optimization job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Production optimization job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
