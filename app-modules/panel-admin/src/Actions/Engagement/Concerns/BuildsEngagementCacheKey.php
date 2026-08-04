<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Engagement\Concerns;

use Carbon\CarbonInterface;
use TresPontosTech\PanelAdmin\DTOs\EngagementFilters;

/**
 * Shared cache-key construction and TTL for the engagement Actions, keeping the
 * cache namespace and expiry in a single place.
 */
trait BuildsEngagementCacheKey
{
    private const int CACHE_TTL_MINUTES = 5;

    private function engagementCacheKey(string $bucket, EngagementFilters $filters): string
    {
        return implode('.', [
            'panel_admin.engagement',
            $bucket,
            $filters->fingerprint(),
        ]);
    }

    private function engagementCacheTtl(): CarbonInterface
    {
        return now()->addMinutes(self::CACHE_TTL_MINUTES);
    }
}
