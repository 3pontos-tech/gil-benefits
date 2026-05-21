<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;

class EngagementStatsWidget extends StatsOverviewWidget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        /** @var Company $tenant */
        $tenant = Filament::getTenant();
        $userId = data_get($this->filters, 'userId');

        $employeesQuery = $tenant->employees()
            ->when($userId, fn ($q) => $q->where('users.id', $userId));

        $totalEmployees = (clone $employeesQuery)->count();

        $activeUsers = (clone $employeesQuery)
            ->whereHas('appointments', fn ($q) => $q
                ->where('company_id', $tenant->id)
                ->whereBetween('appointment_at', [$start, $end])
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
            )
            ->count();

        $inactiveUsers = $totalEmployees - $activeUsers;

        $utilizationRate = $totalEmployees > 0
            ? round($activeUsers / $totalEmployees * 100)
            : 0;

        $firstTimeUsers = (clone $employeesQuery)
            ->whereHas('appointments', fn ($q) => $q
                ->where('company_id', $tenant->id)
                ->whereBetween('appointment_at', [$start, $end])
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
            )
            ->whereDoesntHave('appointments', fn ($q) => $q
                ->where('company_id', $tenant->id)
                ->where('appointment_at', '<', $start)
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
            )
            ->count();

        return [
            Stat::make(__('panel-company::widgets.engagement_stats.active_users'), $activeUsers)
                ->description(__('panel-company::widgets.engagement_stats.active_users_description', ['count' => $activeUsers]))
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success'),

            Stat::make(__('panel-company::widgets.engagement_stats.inactive_users'), $inactiveUsers)
                ->description(__('panel-company::widgets.engagement_stats.inactive_users_description', ['count' => $inactiveUsers]))
                ->descriptionIcon('heroicon-o-user-minus')
                ->color($inactiveUsers > 0 ? 'warning' : 'gray'),

            Stat::make(__('panel-company::widgets.engagement_stats.utilization_rate'), $utilizationRate . '%')
                ->description(__('panel-company::widgets.engagement_stats.utilization_rate_description', [
                    'rate' => $utilizationRate,
                    'total' => $totalEmployees,
                ]))
                ->descriptionIcon('heroicon-o-chart-pie')
                ->color($utilizationRate >= 70 ? 'success' : ($utilizationRate >= 40 ? 'warning' : 'danger')),

            Stat::make(__('panel-company::widgets.engagement_stats.first_time_users'), $firstTimeUsers)
                ->description(__('panel-company::widgets.engagement_stats.first_time_users_description'))
                ->descriptionIcon('heroicon-o-star')
                ->color('info'),
        ];
    }
}
