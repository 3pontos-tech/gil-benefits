<?php

namespace App\Filament\Widgets;

use App\Models\Companies\Company;
use App\Models\Plans\Plan;
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

        $totalCompanies = Company::query()->count();

        return [
            $this->mountActivePlansStat(),
            $this->mountNewUsersStat(),
            Stat::make('Total Companies', $totalCompanies)
                ->description('Overall'),
        ];
    }

    private function mountActivePlansStat(): Stat
    {
        $activePlans = Plan::query()
            ->whereHas('companies', function (Builder $query): void {
                $query->where('company_plans.status', 'active');
            })
            ->count();

        $data = Trend::model(Plan::class)
            ->between(
                start: now()->subDays(7),
                end: now(),
            )
            ->perDay()
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
            ->perDay()
            ->count();

        return Stat::make('New Users', $newUsers)
            ->chart($data->map(fn (TrendValue $value): mixed => $value->aggregate))
            ->color('info')
            ->description('This week');
    }
}
