<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;

/**
 * Resolves the set of employee ids a metrics query should be scoped to.
 *
 * A direct user filter wins; otherwise a department filter is expanded into its
 * members (cached per request). No filter means no scoping (null).
 */
final class ResolveScopedUserIds
{
    public function handle(Company $tenant, MetricsFilters $filters): ?Collection
    {
        if (filled($filters->userId)) {
            return collect([$filters->userId]);
        }

        if (blank($filters->departmentId)) {
            return null;
        }

        $cacheKey = sprintf('metrics.department_users.%s.%s', $tenant->getKey(), $filters->departmentId);

        return Cache::store('array')->rememberForever(
            $cacheKey,
            fn (): Collection => $tenant
                ->employees()
                ->wherePivot('department_id', $filters->departmentId)
                ->pluck('users.id'),
        );
    }
}
