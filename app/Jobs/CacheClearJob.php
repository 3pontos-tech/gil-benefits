<?php

namespace App\Jobs;

use App\Services\CacheService;
use App\Services\ViewCacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CacheClearJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 120; // 2 minutes
    public int $tries = 2;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly array $cacheTypes = ['application', 'views'],
        private readonly ?string $entityType = null,
        private readonly ?int $entityId = null
    ) {
        $this->onQueue('cache');
    }

    /**
     * Execute the job.
     */
    public function handle(CacheService $cacheService, ViewCacheService $viewCacheService): void
    {
        Log::info('Starting cache clear job', [
            'types' => $this->cacheTypes,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
        ]);

        try {
            if ($this->entityType && $this->entityId) {
                // Clear cache for specific entity
                Log::info("Clearing cache for {$this->entityType}:{$this->entityId}");
                $cacheService->invalidateRelatedCache($this->entityType, $this->entityId);
                
                if (in_array('views', $this->cacheTypes)) {
                    if ($this->entityType === 'user') {
                        $viewCacheService->invalidateNavigationCache($this->entityId);
                        $viewCacheService->invalidateWidgetCache($this->entityId);
                    }
                }
            } else {
                // Clear all cache types
                if (in_array('application', $this->cacheTypes)) {
                    Log::info('Clearing application cache');
                    $cacheService->flush();
                }

                if (in_array('views', $this->cacheTypes)) {
                    Log::info('Clearing view cache');
                    $viewCacheService->clearAllViewCache();
                }
            }

            Log::info('Cache clear job completed successfully');

        } catch (\Exception $e) {
            Log::error('Cache clear job failed', [
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
        Log::error('Cache clear job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
