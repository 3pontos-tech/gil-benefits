<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class CacheService
{
    private const DEFAULT_TTL = 3600; // 1 hour
    private const SHORT_TTL = 300; // 5 minutes
    private const MEDIUM_TTL = 1800; // 30 minutes
    private const LONG_TTL = 86400; // 24 hours
    private const STATS_TTL = 900; // 15 minutes

    private const CACHE_PREFIX = 'app_cache:';

    /**
     * Remember a value in cache with automatic key generation.
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->generateKey($key);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        return Cache::remember($cacheKey, $ttl, function () use ($callback, $key) {
            Log::info("Cache miss for key: {$key}");

            return $callback();
        });
    }

    /**
     * Store a value in cache.
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $cacheKey = $this->generateKey($key);
        $ttl = $ttl ?? self::DEFAULT_TTL;

        return Cache::put($cacheKey, $value, $ttl);
    }

    /**
     * Get a value from cache.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->generateKey($key);

        return Cache::get($cacheKey, $default);
    }

    /**
     * Forget a cached value.
     */
    public function forget(string $key): bool
    {
        $cacheKey = $this->generateKey($key);

        return Cache::forget($cacheKey);
    }

    /**
     * Forget multiple cached values by pattern.
     */
    public function forgetByPattern(string $pattern): void
    {
        $fullPattern = self::CACHE_PREFIX . $pattern;

        // For Redis/Memcached, we'd need to implement pattern-based deletion
        // For now, we'll log the pattern for manual cleanup
        Log::info("Cache pattern forget requested: {$fullPattern}");
    }

    /**
     * Flush all cache entries with our prefix.
     */
    public function flush(): bool
    {
        // This is a simplified implementation
        // In production, you might want to use cache tags or a more sophisticated approach
        return Cache::flush();
    }

    /**
     * Generate a cache key with prefix.
     */
    private function generateKey(string $key): string
    {
        return self::CACHE_PREFIX . $key;
    }

    /**
     * Cache user-related data.
     */
    public function cacheUserData(int $userId, string $dataType, mixed $data, ?int $ttl = null): bool
    {
        $key = "user:{$userId}:{$dataType}";

        return $this->put($key, $data, $ttl);
    }

    /**
     * Get cached user data.
     */
    public function getUserData(int $userId, string $dataType, mixed $default = null): mixed
    {
        $key = "user:{$userId}:{$dataType}";

        return $this->get($key, $default);
    }

    /**
     * Forget all cached data for a user.
     */
    public function forgetUserData(int $userId): void
    {
        $this->forgetByPattern("user:{$userId}:*");
    }

    /**
     * Cache company-related data.
     */
    public function cacheCompanyData(int $companyId, string $dataType, mixed $data, ?int $ttl = null): bool
    {
        $key = "company:{$companyId}:{$dataType}";

        return $this->put($key, $data, $ttl);
    }

    /**
     * Get cached company data.
     */
    public function getCompanyData(int $companyId, string $dataType, mixed $default = null): mixed
    {
        $key = "company:{$companyId}:{$dataType}";

        return $this->get($key, $default);
    }

    /**
     * Forget all cached data for a company.
     */
    public function forgetCompanyData(int $companyId): void
    {
        $this->forgetByPattern("company:{$companyId}:*");
    }

    /**
     * Cache appointment statistics.
     *
     * @param  array<string, mixed>  $stats
     */
    public function cacheAppointmentStats(string $type, int $entityId, array $stats, ?int $ttl = null): bool
    {
        $key = "appointment_stats:{$type}:{$entityId}";

        return $this->put($key, $stats, $ttl ?? 1800); // 30 minutes default for stats
    }

    /**
     * Get cached appointment statistics.
     *
     * @return array<string, mixed>|null
     */
    public function getAppointmentStats(string $type, int $entityId): ?array
    {
        $key = "appointment_stats:{$type}:{$entityId}";

        $result = $this->get($key);

        return is_array($result) ? $result : null;
    }

    /**
     * Cache query results with automatic expiration.
     */
    public function cacheQueryResult(string $queryHash, mixed $result, ?int $ttl = null): bool
    {
        $key = "query_result:{$queryHash}";

        return $this->put($key, $result, $ttl ?? 900); // 15 minutes default for query results
    }

    /**
     * Get cached query result.
     */
    public function getQueryResult(string $queryHash): mixed
    {
        $key = "query_result:{$queryHash}";

        return $this->get($key);
    }

    /**
     * Generate a hash for a query and its parameters.
     *
     * @param  array<int, mixed>  $bindings
     */
    public function generateQueryHash(string $query, array $bindings = []): string
    {
        return md5($query . serialize($bindings));
    }

    /**
     * Cache expensive computation results.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function cacheComputation(string $computationType, array $parameters, mixed $result, ?int $ttl = null): bool
    {
        $key = "computation:{$computationType}:" . md5(serialize($parameters));

        return $this->put($key, $result, $ttl ?? 7200); // 2 hours default for computations
    }

    /**
     * Get cached computation result.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function getComputation(string $computationType, array $parameters): mixed
    {
        $key = "computation:{$computationType}:" . md5(serialize($parameters));

        return $this->get($key);
    }

    /**
     * Cache dashboard data with shorter TTL.
     *
     * @param  array<string, mixed>  $data
     */
    public function cacheDashboardData(string $panelType, int $userId, array $data): bool
    {
        $key = "dashboard:{$panelType}:{$userId}";

        return $this->put($key, $data, 600); // 10 minutes for dashboard data
    }

    /**
     * Get cached dashboard data.
     *
     * @return array<string, mixed>|null
     */
    public function getDashboardData(string $panelType, int $userId): ?array
    {
        $key = "dashboard:{$panelType}:{$userId}";

        $result = $this->get($key);

        return is_array($result) ? $result : null;
    }

    /**
     * Cache configuration data with longer TTL.
     */
    public function cacheConfig(string $configKey, mixed $value): bool
    {
        $key = "config:{$configKey}";

        return $this->put($key, $value, 86400); // 24 hours for config data
    }

    /**
     * Get cached configuration data.
     */
    public function getConfig(string $configKey): mixed
    {
        $key = "config:{$configKey}";

        return $this->get($key);
    }

    /**
     * Cache frequently accessed model data with relationships.
     */
    public function cacheModel(Model $model, array $relations = [], ?int $ttl = null): bool
    {
        $key = "model:" . get_class($model) . ":{$model->getKey()}";
        
        if (!empty($relations)) {
            $model->load($relations);
        }
        
        return $this->put($key, $model->toArray(), $ttl ?? self::MEDIUM_TTL);
    }

    /**
     * Get cached model data.
     */
    public function getCachedModel(string $modelClass, mixed $id): ?array
    {
        $key = "model:{$modelClass}:{$id}";
        $result = $this->get($key);
        
        return is_array($result) ? $result : null;
    }

    /**
     * Cache collection with pagination info.
     */
    public function cacheCollection(string $key, Collection $collection, array $meta = [], ?int $ttl = null): bool
    {
        $data = [
            'items' => $collection->toArray(),
            'meta' => $meta,
            'cached_at' => now()->toISOString(),
        ];
        
        return $this->put($key, $data, $ttl ?? self::MEDIUM_TTL);
    }

    /**
     * Get cached collection.
     */
    public function getCachedCollection(string $key): ?array
    {
        return $this->get($key);
    }

    /**
     * Cache navigation menu data.
     */
    public function cacheNavigationMenu(string $panelType, int $userId, array $menuData): bool
    {
        $key = "navigation:{$panelType}:{$userId}";
        return $this->put($key, $menuData, self::LONG_TTL);
    }

    /**
     * Get cached navigation menu.
     */
    public function getNavigationMenu(string $panelType, int $userId): ?array
    {
        $key = "navigation:{$panelType}:{$userId}";
        $result = $this->get($key);
        
        return is_array($result) ? $result : null;
    }

    /**
     * Cache permission data for user.
     */
    public function cacheUserPermissions(int $userId, array $permissions): bool
    {
        $key = "permissions:user:{$userId}";
        return $this->put($key, $permissions, self::LONG_TTL);
    }

    /**
     * Get cached user permissions.
     */
    public function getUserPermissions(int $userId): ?array
    {
        $key = "permissions:user:{$userId}";
        $result = $this->get($key);
        
        return is_array($result) ? $result : null;
    }

    /**
     * Cache role data for user.
     */
    public function cacheUserRoles(int $userId, array $roles): bool
    {
        $key = "roles:user:{$userId}";
        return $this->put($key, $roles, self::LONG_TTL);
    }

    /**
     * Get cached user roles.
     */
    public function getUserRoles(int $userId): ?array
    {
        $key = "roles:user:{$userId}";
        $result = $this->get($key);
        
        return is_array($result) ? $result : null;
    }

    /**
     * Cache system settings.
     */
    public function cacheSystemSettings(array $settings): bool
    {
        return $this->put('system_settings', $settings, self::LONG_TTL);
    }

    /**
     * Get cached system settings.
     */
    public function getSystemSettings(): ?array
    {
        $result = $this->get('system_settings');
        return is_array($result) ? $result : null;
    }

    /**
     * Cache frequently accessed lookup data.
     */
    public function cacheLookupData(string $type, array $data): bool
    {
        $key = "lookup:{$type}";
        return $this->put($key, $data, self::LONG_TTL);
    }

    /**
     * Get cached lookup data.
     */
    public function getLookupData(string $type): ?array
    {
        $key = "lookup:{$type}";
        $result = $this->get($key);
        
        return is_array($result) ? $result : null;
    }

    /**
     * Cache API response data.
     */
    public function cacheApiResponse(string $endpoint, array $params, mixed $response, ?int $ttl = null): bool
    {
        $key = "api_response:" . md5($endpoint . serialize($params));
        return $this->put($key, $response, $ttl ?? self::MEDIUM_TTL);
    }

    /**
     * Get cached API response.
     */
    public function getCachedApiResponse(string $endpoint, array $params): mixed
    {
        $key = "api_response:" . md5($endpoint . serialize($params));
        return $this->get($key);
    }

    /**
     * Cache widget data for dashboard.
     */
    public function cacheWidgetData(string $widgetType, int $userId, array $data): bool
    {
        $key = "widget:{$widgetType}:{$userId}";
        return $this->put($key, $data, self::STATS_TTL);
    }

    /**
     * Get cached widget data.
     */
    public function getWidgetData(string $widgetType, int $userId): ?array
    {
        $key = "widget:{$widgetType}:{$userId}";
        $result = $this->get($key);
        
        return is_array($result) ? $result : null;
    }

    /**
     * Cache search results.
     */
    public function cacheSearchResults(string $query, array $filters, array $results): bool
    {
        $key = "search:" . md5($query . serialize($filters));
        return $this->put($key, $results, self::SHORT_TTL);
    }

    /**
     * Get cached search results.
     */
    public function getCachedSearchResults(string $query, array $filters): ?array
    {
        $key = "search:" . md5($query . serialize($filters));
        $result = $this->get($key);
        
        return is_array($result) ? $result : null;
    }

    /**
     * Cache report data.
     */
    public function cacheReportData(string $reportType, array $parameters, array $data): bool
    {
        $key = "report:{$reportType}:" . md5(serialize($parameters));
        return $this->put($key, $data, self::MEDIUM_TTL);
    }

    /**
     * Get cached report data.
     */
    public function getCachedReportData(string $reportType, array $parameters): ?array
    {
        $key = "report:{$reportType}:" . md5(serialize($parameters));
        $result = $this->get($key);
        
        return is_array($result) ? $result : null;
    }

    /**
     * Warm up cache with frequently accessed data.
     */
    public function warmUpCache(): void
    {
        Log::info('Starting cache warm-up process');
        
        // This method would be called by a scheduled job
        // to pre-populate cache with frequently accessed data
        
        try {
            // Cache system settings
            $this->remember('system_settings_warmup', function () {
                return config('app'); // Example: cache app config
            }, self::LONG_TTL);
            
            // Cache lookup data
            $this->remember('lookup_data_warmup', function () {
                return [
                    'roles' => ['admin', 'user', 'consultant', 'company'],
                    'statuses' => ['active', 'inactive', 'pending'],
                ];
            }, self::LONG_TTL);
            
            Log::info('Cache warm-up completed successfully');
        } catch (\Exception $e) {
            Log::error('Cache warm-up failed: ' . $e->getMessage());
        }
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        try {
            if (config('cache.default') === 'redis') {
                $redis = Redis::connection();
                $info = $redis->info();
                
                return [
                    'driver' => 'redis',
                    'memory_usage' => $info['used_memory_human'] ?? 'N/A',
                    'connected_clients' => $info['connected_clients'] ?? 'N/A',
                    'total_commands_processed' => $info['total_commands_processed'] ?? 'N/A',
                    'keyspace_hits' => $info['keyspace_hits'] ?? 'N/A',
                    'keyspace_misses' => $info['keyspace_misses'] ?? 'N/A',
                ];
            }
            
            return [
                'driver' => config('cache.default'),
                'status' => 'active',
            ];
        } catch (\Exception $e) {
            return [
                'driver' => config('cache.default'),
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Invalidate cache when data changes.
     */
    public function invalidateRelatedCache(string $entityType, int $entityId): void
    {
        switch ($entityType) {
            case 'user':
                $this->forgetUserData($entityId);
                $this->forget("appointment_stats:user:{$entityId}");
                $this->forget("permissions:user:{$entityId}");
                $this->forget("roles:user:{$entityId}");
                $this->forgetByPattern("navigation:*:{$entityId}");
                $this->forgetByPattern("dashboard:*:{$entityId}");
                $this->forgetByPattern("widget:*:{$entityId}");
                break;

            case 'company':
                $this->forgetCompanyData($entityId);
                $this->forget("appointment_stats:company:{$entityId}");
                break;

            case 'appointment':
                $this->forgetByPattern('appointment_stats:*');
                $this->forgetByPattern('widget:*');
                $this->forgetByPattern('dashboard:*');
                break;

            case 'consultant':
                $this->forget("appointment_stats:consultant:{$entityId}");
                break;

            case 'system_settings':
                $this->forget('system_settings');
                $this->forget('system_settings_warmup');
                break;

            case 'permissions':
                $this->forgetByPattern('permissions:*');
                $this->forgetByPattern('navigation:*');
                break;

            case 'roles':
                $this->forgetByPattern('roles:*');
                $this->forgetByPattern('navigation:*');
                break;
        }
    }

    /**
     * Clear expired cache entries (for cleanup jobs).
     */
    public function clearExpiredEntries(): int
    {
        // This is driver-specific implementation
        // For Redis, expired keys are automatically removed
        // For database cache, we can clean up expired entries
        
        if (config('cache.default') === 'database') {
            return \DB::table(config('cache.stores.database.table'))
                ->where('expiration', '<', now()->timestamp)
                ->delete();
        }
        
        return 0;
    }
}
