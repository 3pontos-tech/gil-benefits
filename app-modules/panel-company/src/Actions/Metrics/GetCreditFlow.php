<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\CreditFlow;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Credit flow scoped to the window: credits distributed and used within it
 * (raw data; scoped by holder).
 */
final class GetCreditFlow
{
    use BuildsMetricsCacheKey;

    public function __construct(private readonly ResolveScopedUserIds $resolveScopedUserIds) {}

    public function handle(Company $tenant, MetricsPeriod $period, MetricsFilters $filters): CreditFlow
    {
        $userIds = $this->resolveScopedUserIds->handle($tenant, $filters);

        $cacheKey = $this->metricsCacheKey('credit_flow', $tenant, $period->cacheKey(), $filters->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $userIds): CreditFlow {
            $base = UserCredit::forCompany($tenant)->heldBy($userIds);

            $distributed = (clone $base)
                ->whereNotNull('transferred_at')
                ->whereBetween('transferred_at', [$period->start, $period->end])
                ->count();

            $usedInPeriod = (clone $base)
                ->where('status', UserCreditStatusEnum::Used)
                ->whereHas('appointment', fn ($q) => $q->whereBetween('appointment_at', [$period->start, $period->end]))
                ->count();

            return new CreditFlow($distributed, $usedInPeriod);
        });
    }
}
