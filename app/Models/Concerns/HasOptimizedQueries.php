<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

trait HasOptimizedQueries
{
    /**
     * Scope to eager load common relationships to prevent N+1 queries.
     */
    public function scopeWithCommonRelations(Builder $query): void
    {
        // Override in each model to define common relationships
    }

    /**
     * Scope to include only active (non-deleted) records.
     */
    public function scopeActive(Builder $query): void
    {
        if (in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(static::class))) {
            $query->whereNull($this->getQualifiedDeletedAtColumn());
        }
    }

    /**
     * Scope to order by creation date (newest first).
     */
    public function scopeLatest(Builder $query): void
    {
        $query->orderBy($this->getQualifiedCreatedAtColumn(), 'desc');
    }

    /**
     * Scope to order by creation date (oldest first).
     */
    public function scopeOldest(Builder $query): void
    {
        $query->orderBy($this->getQualifiedCreatedAtColumn(), 'asc');
    }
}
