<?php

namespace App\Repositories\Concerns;

use App\Services\CacheService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

trait Cacheable
{
    protected CacheService $cacheService;

    /**
     * Cache a query result with automatic key generation.
     */
    protected function cacheQuery(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return $this->cacheService->remember($key, $callback, $ttl);
    }

    /**
     * Cache model statistics.
     */
    protected function cacheModelStats(Model $model, array $stats, ?int $ttl = null): bool
    {
        $modelType = class_basename($model);
        $key = strtolower($modelType) . '_stats:' . $model->getKey();

        return $this->cacheService->put($key, $stats, $ttl ?? 1800);
    }

    /**
     * Get cached model statistics.
     */
    protected function getCachedModelStats(Model $model): ?array
    {
        $modelType = class_basename($model);
        $key = strtolower($modelType) . '_stats:' . $model->getKey();

        return $this->cacheService->get($key);
    }

    /**
     * Invalidate cache for a model.
     */
    protected function invalidateModelCache(Model $model): void
    {
        $modelType = strtolower(class_basename($model));
        $this->cacheService->invalidateRelatedCache($modelType, $model->getKey());
    }

    /**
     * Cache a collection with pagination info.
     */
    protected function cachePaginatedResult(string $key, callable $callback, ?int $ttl = null): mixed
    {
        return $this->cacheQuery($key, $callback, $ttl ?? 600); // 10 minutes default for paginated results
    }

    /**
     * Cache expensive aggregation queries.
     */
    protected function cacheAggregation(string $operation, string $table, array $conditions, callable $callback, ?int $ttl = null): mixed
    {
        $key = "aggregation:{$operation}:{$table}:" . md5(serialize($conditions));

        return $this->cacheQuery($key, $callback, $ttl ?? 1800); // 30 minutes for aggregations
    }

    /**
     * Cache relationship data.
     */
    protected function cacheRelationship(Model $model, string $relationship, callable $callback, ?int $ttl = null): mixed
    {
        $modelType = class_basename($model);
        $key = strtolower($modelType) . ':' . $model->getKey() . ':' . $relationship;

        return $this->cacheQuery($key, $callback, $ttl ?? 900); // 15 minutes for relationships
    }

    /**
     * Set the cache service instance.
     */
    public function setCacheService(CacheService $cacheService): self
    {
        $this->cacheService = $cacheService;

        return $this;
    }
}
