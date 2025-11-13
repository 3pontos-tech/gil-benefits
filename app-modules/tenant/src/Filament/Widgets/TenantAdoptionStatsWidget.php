<?php

namespace TresPontosTech\Tenant\Filament\Widgets;

use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\IconPosition;
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

        return Stat::make('Employees', $employeesCount)
            ->description('Users')
            ->descriptionIcon('heroicon-o-user-group')
            ->color('primary');
    }

    private function getEmployeesWithAccessCount(): Stat
    {
        $tenant = Filament::getTenant();
        $totalEmployees = $tenant->employees()->count();

        $employeesWithAccess = $tenant->employees()
            ->whereNotNull('email_verified_at')
            ->count();

        $percentage = $totalEmployees > 0
            ? round(($employeesWithAccess / $totalEmployees) * 100, 1)
            : 0;

        return Stat::make('Employees with access', $employeesWithAccess)
            ->description("{$percentage}% of total ({$employeesWithAccess}/{$totalEmployees})")
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
            ->whereNotNull('email_verified_at')
            ->whereHas('subscriptions')
            ->count();

        $percentage = $employeesWithAccess > 0
            ? round(($employeesWithPlans / $employeesWithAccess) * 100, 1)
            : 0;

        return Stat::make('Employees with plans', $employeesWithPlans)
            ->description("{$percentage}% of those with access ({$employeesWithPlans}/{$employeesWithAccess})")
            ->descriptionIcon('heroicon-o-credit-card')
            ->color('success');
    }

    private function getAdoptionRate(): Stat
    {
        $tenant = Filament::getTenant();
        $totalEmployees = $tenant->employees()->count();

        $employeesWithPlans = $tenant->employees()
            ->whereNotNull('email_verified_at')
            ->whereHas('subscriptions')
            ->count();

        $adoptionRate = $totalEmployees > 0
            ? round(($employeesWithPlans / $totalEmployees) * 100, 1)
            : 0;

        return Stat::make('Adoption Rate', "{$adoptionRate}%")
            ->description("{$employeesWithPlans} of {$totalEmployees} employees")
            ->descriptionIcon('heroicon-o-chart-bar')
            ->color($adoptionRate >= 70 ? Color::Cyan : ($adoptionRate >= 30 ? Color::Amber : Color::Red));
    }
}
