<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\CategoryMix;
use TresPontosTech\PanelCompany\DTOs\CategorySlice;
use TresPontosTech\PanelCompany\DTOs\MetricsFilters;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

/**
 * Appointment mix by category within the window (raw data; no SVG).
 */
final class GetCategoryMix
{
    use BuildsMetricsCacheKey;

    /** @var array<int, string> */
    private const COLORS = ['primary', 'teal', 'violet', 'pink', 'amber', 'blue'];

    public function __construct(private readonly ResolveScopedUserIds $resolveScopedUserIds) {}

    public function handle(Company $tenant, MetricsPeriod $period, MetricsFilters $filters): CategoryMix
    {
        $userIds = $this->resolveScopedUserIds->handle($tenant, $filters);

        $cacheKey = $this->metricsCacheKey('category_mix', $tenant, $period->cacheKey(), $filters->cacheKey());

        return Cache::remember($cacheKey, $this->metricsCacheTtl(), function () use ($tenant, $period, $userIds): CategoryMix {
            $results = Appointment::forCompany($tenant)
                ->betweenDates($period->start, $period->end)
                ->forUsers($userIds)
                ->whereNotNull('category_type')
                ->selectRaw('category_type, count(*) as total')
                ->groupBy('category_type')
                ->orderByDesc('total')
                ->pluck('total', 'category_type');

            $total = (int) $results->sum();

            if ($total === 0) {
                return new CategoryMix(items: [], total: 0);
            }

            $items = [];
            $index = 0;

            foreach ($results as $category => $count) {
                $items[] = new CategorySlice(
                    label: AppointmentCategoryEnum::tryFrom((string) $category)?->getLabel() ?? (string) $category,
                    value: (int) $count,
                    percent: $this->rate((int) $count, $total),
                    color: self::COLORS[$index % count(self::COLORS)],
                );
                ++$index;
            }

            return new CategoryMix(items: $items, total: $total);
        });
    }

    private function rate(int $value, int $total): float
    {
        return $total > 0 ? round($value / $total * 100, 1) : 0.0;
    }
}
