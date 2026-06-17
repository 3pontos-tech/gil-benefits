<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Actions\Metrics;

use Illuminate\Support\Facades\Cache;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\Concerns\BuildsMetricsCacheKey;
use TresPontosTech\PanelCompany\DTOs\FunnelData;
use TresPontosTech\PanelCompany\DTOs\FunnelStep;

/**
 * Current-snapshot adoption funnel for a tenant: invited employees, those with
 * access (verified email) and those with an active plan.
 */
final class GetAdoptionFunnel
{
    use BuildsMetricsCacheKey;

    public function handle(Company $tenant): FunnelData
    {
        return Cache::remember(
            $this->metricsCacheKey('adoption_funnel', $tenant),
            $this->metricsCacheTtl(),
            fn (): FunnelData => $this->build($tenant),
        );
    }

    private function build(Company $tenant): FunnelData
    {
        $invited = $tenant->onlyEmployees()->count();
        $withAccess = $tenant->onlyEmployees()->whereNotNull('email_verified_at')->count();
        $withPlan = $tenant->onlyEmployees()->whereHas('subscriptions')->count();
        $newThisMonth = $tenant->onlyEmployees()
            ->wherePivot('created_at', '>=', now()->startOfMonth())
            ->count();

        $adoptionRate = $this->rate($withPlan, $invited);

        $steps = [
            new FunnelStep(
                trans('panel-company::resources.pages.command_dashboard.funnel.invited'),
                $invited,
                $this->rate($invited, $invited),
            ),
            new FunnelStep(
                trans('panel-company::resources.pages.command_dashboard.funnel.with_access'),
                $withAccess,
                $this->rate($withAccess, $invited),
            ),
            new FunnelStep(
                trans('panel-company::resources.pages.command_dashboard.funnel.active_plan'),
                $withPlan,
                $adoptionRate,
            ),
        ];

        return new FunnelData(
            invited: $invited,
            withAccess: $withAccess,
            withPlan: $withPlan,
            adoptionRate: $adoptionRate,
            noAccess: max(0, $invited - $withAccess),
            accessNoPlan: max(0, $withAccess - $withPlan),
            newThisMonth: $newThisMonth,
            steps: $steps,
        );
    }

    private function rate(int $value, int $total): float
    {
        return $total > 0 ? round($value / $total * 100, 1) : 0.0;
    }
}
