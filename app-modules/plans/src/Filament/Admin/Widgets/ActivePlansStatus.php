<?php

namespace TresPontosTech\Plans\Filament\Admin\Widgets;

use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Database\Eloquent\Builder;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Plans\Models\Plan;

class ActivePlansStatus extends ChartWidget
{
    protected ?string $heading = 'Active Plans Status';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $datasets = [];
        $labels = [];

        $plans = Plan::query()->get();
        foreach ($plans as $plan) {
            $data = Trend::query(Company::query()
                ->whereHas('plans', function (Builder $query) use ($plan): void {
                    $query->where('plan_id', $plan->id)
                        ->where('company_plans.status', 'active');
                }))
                ->between(
                    start: now()->startOfMonth(),
                    end: now()->endOfMonth(),
                )
                ->perMonth()
                ->count();

            $datasets[] = [
                'label' => $plan->name,
                'data' => $data->map(fn (TrendValue $value): mixed => $value->aggregate),
                'borderWidth' => 1,
            ];

            if (empty($labels)) {
                $labels = $data->map(fn (TrendValue $value): string => $value->date)->all();
            }
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
