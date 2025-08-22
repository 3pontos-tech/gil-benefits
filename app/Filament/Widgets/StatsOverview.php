<?php

namespace App\Filament\Widgets;

use App\Models\Companies\Company;
use App\Models\Users\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Database\Eloquent\Builder;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {

        Company::query()->count();

        return [
            $this->mountActivePlansStat(),
            $this->mountNewUsersStat(),
            $this->mountTotalCompaniesStat(),
        ];
    }

    private function mountActivePlansStat(): Stat
    {
        $activePlans = Company::query()
            ->whereHas('plans', function (Builder $query): void {
                $query->where('company_plans.status', 'active');
            })
            ->count();

        $data = Trend::query(Company::query()
            ->whereHas('plans', function (Builder $query): void {
                $query->where('company_plans.status', 'active');
            }))
            ->between(
                start: now()->subDays(7),
                end: now(),
            )
            ->perWeek()
            ->count();

        return Stat::make('Active Plans', $activePlans)
            ->chart($data->map(fn (TrendValue $value): mixed => $value->aggregate))
            ->color('success')
            ->description('Current active plans');
    }

    private function mountNewUsersStat(): Stat
    {
        $newUsers = User::query()
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        $data = Trend::model(User::class)
            ->between(
                start: now()->subDays(7),
                end: now(),
            )
            ->perWeek()
            ->count();

        return Stat::make('New Users', $newUsers)
            ->chart($data->map(fn (TrendValue $value): mixed => $value->aggregate))
            ->color('info')
            ->description('This week');
    }

    private function mountTotalCompaniesStat(): Stat
    {
        $totalCompanies = Company::query()->count();

        $data = Trend::model(Company::class)
            ->between(
                start: now()->startOfCentury(),
                end: now()->endOfCentury(),
            )
            ->perYear()
            ->count();

        return Stat::make('Total Companies', $totalCompanies)
            ->chart($data->map(fn (TrendValue $value): mixed => $value->aggregate))
            ->color('danger')
            ->description('Overall');
    }
}
