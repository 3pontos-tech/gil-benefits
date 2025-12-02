<?php

namespace App\Jobs;

use App\Services\CacheService;
use App\Services\ViewCacheService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CacheWarmupJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public int $timeout = 300; // 5 minutes
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly array $cacheTypes = ['application', 'views']
    ) {
        $this->onQueue('cache');
    }

    /**
     * Execute the job.
     */
    public function handle(CacheService $cacheService, ViewCacheService $viewCacheService): void
    {
        Log::info('Starting cache warmup job', ['types' => $this->cacheTypes]);

        try {
            if (in_array('application', $this->cacheTypes)) {
                Log::info('Warming up application cache');
                $cacheService->warmUpCache();
            }

            if (in_array('views', $this->cacheTypes)) {
                Log::info('Warming up view cache');
                $viewCacheService->warmUpViewCache();
            }

            Log::info('Cache warmup job completed successfully');

        } catch (\Exception $e) {
            Log::error('Cache warmup job failed', [
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
        Log::error('Cache warmup job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }
}
