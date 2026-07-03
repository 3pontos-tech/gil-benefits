<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics\Concerns;

use Carbon\CarbonInterface;
use TresPontosTech\Company\Models\Company;

/**
 * Shared cache-key construction and TTL for the metrics Actions, keeping the
 * cache namespace and expiry in a single place.
 */
trait BuildsMetricsCacheKey
{
    private const int CACHE_TTL_MINUTES = 5;

    /**
     * Builds a namespaced cache key for a metrics bucket. Extra segments (period,
     * filters, status, ...) are appended verbatim in the order given.
     */
    private function metricsCacheKey(string $bucket, Company $tenant, string ...$segments): string
    {
        return implode('.', [
            'panel_company.metrics',
            $bucket,
            (string) $tenant->getKey(),
            ...$segments,
        ]);
    }

    private function metricsCacheTtl(): CarbonInterface
    {
        return now()->addMinutes(self::CACHE_TTL_MINUTES);
    }
}
