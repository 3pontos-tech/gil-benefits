<?php

namespace App\Services;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class RouteCacheService
{
    private const CACHE_TTL = 86400; // 24 hours
    private const CACHE_PREFIX = 'route_cache:';

    /**
     * Cache all application routes for production.
     */
    public function cacheRoutes(): bool
    {
        try {
            Log::info('Starting route caching process');
            
            // Clear existing route cache
            Artisan::call('route:clear');
            
            // Cache routes
            Artisan::call('route:cache');
            
            // Verify cache was created
            $cacheFile = base_path('bootstrap/cache/routes-v7.php');
            if (File::exists($cacheFile)) {
                Log::info('Route cache created successfully');
                return true;
            }
            
            Log::error('Route cache file was not created');
            return false;
            
        } catch (\Exception $e) {
            Log::error('Route caching failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear route cache.
     */
    public function clearRouteCache(): bool
    {
        try {
            Log::info('Clearing route cache');
            
            Artisan::call('route:clear');
            
            $cacheFile = base_path('bootstrap/cache/routes-v7.php');
            if (!File::exists($cacheFile)) {
                Log::info('Route cache cleared successfully');
                return true;
            }
            
            Log::error('Route cache file still exists after clearing');
            return false;
            
        } catch (\Exception $e) {
            Log::error('Route cache clearing failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cache configuration files for production.
     */
    public function cacheConfig(): bool
    {
        try {
            Log::info('Starting configuration caching process');
            
            // Clear existing config cache
            Artisan::call('config:clear');
            
            // Cache configuration
            Artisan::call('config:cache');
            
            // Verify cache was created
            $cacheFile = base_path('bootstrap/cache/config.php');
            if (File::exists($cacheFile)) {
                Log::info('Configuration cache created successfully');
                return true;
            }
            
            Log::error('Configuration cache file was not created');
            return false;
            
        } catch (\Exception $e) {
            Log::error('Configuration caching failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear configuration cache.
     */
    public function clearConfigCache(): bool
    {
        try {
            Log::info('Clearing configuration cache');
            
            Artisan::call('config:clear');
            
            $cacheFile = base_path('bootstrap/cache/config.php');
            if (!File::exists($cacheFile)) {
                Log::info('Configuration cache cleared successfully');
                return true;
            }
            
            Log::error('Configuration cache file still exists after clearing');
            return false;
            
        } catch (\Exception $e) {
            Log::error('Configuration cache clearing failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cache views for production.
     */
    public function cacheViews(): bool
    {
        try {
            Log::info('Starting view caching process');
            
            // Clear existing view cache
            Artisan::call('view:clear');
            
            // Cache views
            Artisan::call('view:cache');
            
            Log::info('View cache created successfully');
            return true;
            
        } catch (\Exception $e) {
            Log::error('View caching failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear view cache.
     */
    public function clearViewCache(): bool
    {
        try {
            Log::info('Clearing view cache');
            
            Artisan::call('view:clear');
            
            Log::info('View cache cleared successfully');
            return true;
            
        } catch (\Exception $e) {
            Log::error('View cache clearing failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cache events for production.
     */
    public function cacheEvents(): bool
    {
        try {
            Log::info('Starting event caching process');
            
            // Clear existing event cache
            Artisan::call('event:clear');
            
            // Cache events
            Artisan::call('event:cache');
            
            // Verify cache was created
            $cacheFile = base_path('bootstrap/cache/events.php');
            if (File::exists($cacheFile)) {
                Log::info('Event cache created successfully');
                return true;
            }
            
            Log::info('Event cache not created (no events to cache)');
            return true;
            
        } catch (\Exception $e) {
            Log::error('Event caching failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear event cache.
     */
    public function clearEventCache(): bool
    {
        try {
            Log::info('Clearing event cache');
            
            Artisan::call('event:clear');
            
            Log::info('Event cache cleared successfully');
            return true;
            
        } catch (\Exception $e) {
            Log::error('Event cache clearing failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Optimize application for production.
     */
    public function optimizeForProduction(): array
    {
        $results = [];
        
        Log::info('Starting production optimization');
        
        // Cache routes
        $results['routes'] = $this->cacheRoutes();
        
        // Cache configuration
        $results['config'] = $this->cacheConfig();
        
        // Cache views
        $results['views'] = $this->cacheViews();
        
        // Cache events
        $results['events'] = $this->cacheEvents();
        
        // Run optimize command
        try {
            Artisan::call('optimize');
            $results['optimize'] = true;
            Log::info('Application optimization completed');
        } catch (\Exception $e) {
            $results['optimize'] = false;
            Log::error('Application optimization failed: ' . $e->getMessage());
        }
        
        return $results;
    }

    /**
     * Clear all optimization caches.
     */
    public function clearAllOptimizations(): array
    {
        $results = [];
        
        Log::info('Clearing all optimization caches');
        
        // Clear routes
        $results['routes'] = $this->clearRouteCache();
        
        // Clear configuration
        $results['config'] = $this->clearConfigCache();
        
        // Clear views
        $results['views'] = $this->clearViewCache();
        
        // Clear events
        $results['events'] = $this->clearEventCache();
        
        // Run optimize:clear command
        try {
            Artisan::call('optimize:clear');
            $results['optimize_clear'] = true;
            Log::info('All optimization caches cleared');
        } catch (\Exception $e) {
            $results['optimize_clear'] = false;
            Log::error('Optimization cache clearing failed: ' . $e->getMessage());
        }
        
        return $results;
    }

    /**
     * Get optimization status.
     */
    public function getOptimizationStatus(): array
    {
        return [
            'routes_cached' => File::exists(base_path('bootstrap/cache/routes-v7.php')),
            'config_cached' => File::exists(base_path('bootstrap/cache/config.php')),
            'events_cached' => File::exists(base_path('bootstrap/cache/events.php')),
            'views_cached' => $this->areViewsCached(),
            'environment' => config('app.env'),
            'debug_mode' => config('app.debug'),
        ];
    }

    /**
     * Check if views are cached.
     */
    private function areViewsCached(): bool
    {
        $viewCacheDir = storage_path('framework/views');
        
        if (!File::isDirectory($viewCacheDir)) {
            return false;
        }
        
        $files = File::files($viewCacheDir);
        return count($files) > 0;
    }

    /**
     * Get cache file sizes.
     */
    public function getCacheFileSizes(): array
    {
        $sizes = [];
        
        $cacheFiles = [
            'routes' => base_path('bootstrap/cache/routes-v7.php'),
            'config' => base_path('bootstrap/cache/config.php'),
            'events' => base_path('bootstrap/cache/events.php'),
        ];
        
        foreach ($cacheFiles as $type => $file) {
            if (File::exists($file)) {
                $sizes[$type] = File::size($file);
            } else {
                $sizes[$type] = 0;
            }
        }
        
        // View cache directory size
        $viewCacheDir = storage_path('framework/views');
        if (File::isDirectory($viewCacheDir)) {
            $sizes['views'] = $this->getDirectorySize($viewCacheDir);
        } else {
            $sizes['views'] = 0;
        }
        
        return $sizes;
    }

    /**
     * Get directory size recursively.
     */
    private function getDirectorySize(string $directory): int
    {
        $size = 0;
        
        foreach (File::allFiles($directory) as $file) {
            $size += $file->getSize();
        }
        
        return $size;
    }

    /**
     * Schedule optimization for production deployment.
     */
    public function scheduleOptimization(): void
    {
        Cache::put(self::CACHE_PREFIX . 'optimization_scheduled', true, self::CACHE_TTL);
        Log::info('Production optimization scheduled');
    }

    /**
     * Check if optimization is scheduled.
     */
    public function isOptimizationScheduled(): bool
    {
        return Cache::get(self::CACHE_PREFIX . 'optimization_scheduled', false);
    }

    /**
     * Mark optimization as completed.
     */
    public function markOptimizationCompleted(): void
    {
        Cache::forget(self::CACHE_PREFIX . 'optimization_scheduled');
        Cache::put(self::CACHE_PREFIX . 'last_optimization', now(), self::CACHE_TTL);
        Log::info('Production optimization marked as completed');
    }

    /**
     * Get last optimization timestamp.
     */
    public function getLastOptimizationTime(): ?string
    {
        $timestamp = Cache::get(self::CACHE_PREFIX . 'last_optimization');
        return $timestamp ? $timestamp->toISOString() : null;
    }
}