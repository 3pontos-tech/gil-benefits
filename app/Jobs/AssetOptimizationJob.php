<?php

namespace App\Jobs;

use App\Services\AssetOptimizationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AssetOptimizationJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 600; // 10 minutes
    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly array $optimizationTypes = ['build', 'images', 'compress']
    ) {
        $this->onQueue('assets');
    }

    /**
     * Execute the job.
     */
    public function handle(AssetOptimizationService $assetService): void
    {
        Log::info('Starting asset optimization job', ['types' => $this->optimizationTypes]);

        $results = [];

        try {
            if (in_array('build', $this->optimizationTypes)) {
                Log::info('Building assets');
                $results['build'] = $assetService->buildAssets();
            }

            if (in_array('images', $this->optimizationTypes)) {
                Log::info('Optimizing images');
                $results['images'] = $assetService->optimizeImages();
                
                Log::info('Generating WebP images');
                $results['webp'] = $assetService->generateWebPImages();
            }

            if (in_array('compress', $this->optimizationTypes)) {
                Log::info('Compressing assets');
                $results['compress'] = $assetService->compressAssets();
            }

            // Clean old builds
            Log::info('Cleaning old builds');
            $results['cleanup'] = $assetService->cleanOldBuilds();

            // Cache results
            $assetService->cacheOptimizationResults($results);

            Log::info('Asset optimization job completed successfully', ['results' => $results]);

        } catch (\Exception $e) {
            Log::error('Asset optimization job failed', [
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
        Log::error('Asset optimization job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
