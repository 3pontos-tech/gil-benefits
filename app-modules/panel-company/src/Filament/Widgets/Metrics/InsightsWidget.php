<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use App\Models\Users\User;
use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Collection;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;

class InsightsWidget extends StatsOverviewWidget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $userIds = $this->filteredUserIds();
        $userId = data_get($this->pageFilters, 'userId');

        if ($tenant->employees()->count() === 0) {
            return [$this->noDataStat()];
        }

        return array_filter([
            $this->neverUsedStat($tenant, $userIds),
            $this->volumeVariationStat($tenant->id, $userIds),
            filled($userId) ? null : $this->topUserStat($tenant, $userIds),
        ]);
    }

    /**
     * @param  Collection<int, string>|null  $userIds
     */
    private function neverUsedStat(Company $tenant, ?Collection $userIds): ?Stat
    {
        $employeesQuery = $tenant->employees()
            ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('users.id', $userIds));

        $totalEmployees = (clone $employeesQuery)->count();

        if ($totalEmployees === 0) {
            return null;
        }

        $everUsedCount = (clone $employeesQuery)
            ->whereHas('appointments', fn ($q) => $q
                ->where('company_id', $tenant->id)
                ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds))
            )
            ->count();

        $neverUsedCount = $totalEmployees - $everUsedCount;
        $neverUsedRate = round($neverUsedCount / $totalEmployees * 100);

        return Stat::make(
            __('panel-company::widgets.insights.not_used_benefit', ['rate' => $neverUsedRate]),
            $neverUsedCount . '/' . $totalEmployees
        )
            ->description(__('panel-company::widgets.insights.not_used_benefit_description', [
                'count' => $neverUsedCount,
                'total' => $totalEmployees,
            ]))
            ->descriptionIcon('heroicon-o-exclamation-circle')
            ->color($neverUsedRate > 50 ? 'danger' : ($neverUsedRate > 20 ? 'warning' : 'success'));
    }

    /**
     * @param  Collection<int, string>|null  $userIds
     */
    private function volumeVariationStat(string $tenantId, ?Collection $userIds): ?Stat
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $durationDays = (int) $start->diffInDays($end);
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($durationDays);

        $currentTotal = Appointment::query()
            ->where('company_id', $tenantId)
            ->whereBetween('appointment_at', [$start, $end])
            ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds))
            ->count();

        $previousTotal = Appointment::query()
            ->where('company_id', $tenantId)
            ->whereBetween('appointment_at', [$prevStart, $prevEnd])
            ->when($userIds instanceof Collection, fn ($q) => $q->whereIn('user_id', $userIds))
            ->count();

        if ($previousTotal === 0) {
            return null;
        }

        $variation = round(($currentTotal - $previousTotal) / $previousTotal * 100);

        [$label, $color, $icon] = match (true) {
            $variation > 0 => [
                __('panel-company::widgets.insights.volume_increase', ['rate' => abs($variation)]),
                'success',
                'heroicon-o-arrow-trending-up',
            ],
            $variation < 0 => [
                __('panel-company::widgets.insights.volume_decrease', ['rate' => abs($variation)]),
                'danger',
                'heroicon-o-arrow-trending-down',
            ],
            default => [
                __('panel-company::widgets.insights.volume_stable'),
                'gray',
                'heroicon-o-minus',
            ],
        };

        return Stat::make($label, $currentTotal)
            ->description(__('panel-company::widgets.insights.volume_comparison_description', [
                'current' => $currentTotal,
                'previous' => $previousTotal,
            ]))
            ->descriptionIcon($icon)
            ->color($color);
    }

    /**
     * @param  Collection<int, string>|null  $userIds
     */
    private function topUserStat(Company $tenant, ?Collection $userIds = null): ?Stat
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $employeeIds = $userIds ?? $tenant->employees()->pluck('users.id');

        $topData = Appointment::query()
            ->where('company_id', $tenant->id)
            ->whereBetween('appointment_at', [$start, $end])
            ->whereIn('user_id', $employeeIds)
            ->selectRaw('user_id, count(*) as period_count')
            ->groupBy('user_id')
            ->orderByDesc('period_count')
            ->toBase()
            ->first();

        if (! $topData) {
            return null;
        }

        $topUser = $tenant->employees()->find((string) $topData->user_id);

        if (! $topUser instanceof User) {
            return null;
        }

        return Stat::make(__('panel-company::widgets.insights.top_user'), $topUser->name)
            ->description(__('panel-company::widgets.insights.top_user_description', [
                'name' => $topUser->name,
                'count' => $topData->period_count,
            ]))
            ->descriptionIcon('heroicon-o-trophy')
            ->color('info');
    }

    private function noDataStat(): Stat
    {
        return Stat::make(__('panel-company::widgets.insights.no_data'), '—')
            ->description(__('panel-company::widgets.insights.no_data_description'))
            ->color('gray');
    }
}
