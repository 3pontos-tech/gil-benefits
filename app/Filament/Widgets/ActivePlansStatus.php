<?php

namespace App\Filament\Widgets;

use App\Models\Companies\Company;
use App\Models\Plans\Plan;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Database\Eloquent\Builder;

class ActivePlansStatus extends ChartWidget
{
    protected ?string $heading = 'Active Plans Status';

    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $plans = Plan::query()->get();
        foreach ($plans as $plan) {
            $data = Trend::query(Company::query()
                ->whereHas('plans', function (Builder $query): void {
                    $query->where('company_plans.status', 'active');
                }))
                ->between(
                    start: now()->startOfMonth(),
                    end: now()->endOfMonth(),
                )
                ->perMonth()
                ->count();

            $datasets[] = [
                'label' => $plans->get('name'),
                'data' => $data->map(fn (TrendValue $value): mixed => $value->aggregate),
            ];
        }

        $planLabels = $data->map(fn (TrendValue $value): string => $value->date);

        return [
            'datasets' => $datasets,
            'labels' => $planLabels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
