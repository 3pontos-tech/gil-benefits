<?php

namespace TresPontosTech\PanelCompany\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TenantAdoptionStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            $this->getEmployeesCount(),
            $this->getEmployeesWithAccessCount(),
            $this->getEmployeesWithPlansCount(),
            $this->getAdoptionRate(),
        ];
    }

    private function getEmployeesCount(): Stat
    {
        $employeesCount = Filament::getTenant()->employees()->count();

        return Stat::make(__('panel-company::widgets.adoption_stats.employees'), $employeesCount)
            ->description(__('panel-company::widgets.adoption_stats.members'))
            ->descriptionIcon('heroicon-o-user-group')
            ->color('primary');
    }

    private function getEmployeesWithAccessCount(): Stat
    {
        $tenant = Filament::getTenant();
        $totalEmployees = $tenant->employees()->count();

        $employeesWithAccess = $tenant->employees()
            ->count();

        $percentage = $totalEmployees > 0
            ? round(($employeesWithAccess / $totalEmployees) * 100, 1)
            : 0;

        return Stat::make(__('panel-company::widgets.adoption_stats.employees_with_access'), $employeesWithAccess)
            ->description(sprintf(__('panel-company::widgets.adoption_stats.percentage_of_total'), $percentage, $employeesWithAccess, $totalEmployees))
            ->descriptionIcon('heroicon-o-user-group')
            ->color(Color::Emerald);
    }

    private function getEmployeesWithPlansCount(): Stat
    {
        $tenant = Filament::getTenant();

        $employeesWithAccess = $tenant->employees()
            ->whereNotNull('email_verified_at')
            ->count();

        $employeesWithPlans = $tenant->employees()
            ->whereHas('subscriptions')
            ->count();

        $percentage = $employeesWithAccess > 0
            ? round(($employeesWithPlans / $employeesWithAccess) * 100, 1)
            : 0;

        return Stat::make(__('panel-company::widgets.adoption_stats.employees_with_plan'), $employeesWithPlans)
            ->description(sprintf(__('panel-company::widgets.adoption_stats.percentage_of_access'), $percentage, $employeesWithPlans, $employeesWithAccess))
            ->descriptionIcon('heroicon-o-credit-card')
            ->color('success');
    }

    private function getAdoptionRate(): Stat
    {
        $tenant = Filament::getTenant();
        $totalEmployees = $tenant->employees()->count();

        $employeesWithPlans = $tenant->employees()
            ->whereHas('subscriptions')
            ->count();

        $adoptionRate = $totalEmployees > 0
            ? round(($employeesWithPlans / $totalEmployees) * 100, 1)
            : 0;

        return Stat::make(__('panel-company::widgets.adoption_stats.adoption_rate'), $adoptionRate . '%')
            ->description(sprintf(__('panel-company::widgets.adoption_stats.x_of_y_employees'), $employeesWithPlans, $totalEmployees))
            ->descriptionIcon('heroicon-o-chart-bar')
            ->color($adoptionRate >= 70 ? Color::Cyan : ($adoptionRate >= 30 ? Color::Amber : Color::Red));
    }
}
