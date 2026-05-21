<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Company\Models\Company;

class InsightsWidget extends StatsOverviewWidget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $userId = data_get($this->filters, 'userId');

        if ($tenant->employees()->count() === 0) {
            return [$this->noDataStat()];
        }

        return array_filter([
            $this->neverUsedStat($tenant, $userId),
            $this->volumeVariationStat($tenant->id, $userId),
            $userId ? null : $this->topUserStat($tenant),
        ]);
    }

    private function neverUsedStat(Company $tenant, ?string $userId): Stat
    {
        $employeesQuery = $tenant->employees()
            ->when($userId, fn ($q) => $q->where('users.id', $userId));

        $totalEmployees = (clone $employeesQuery)->count();

        $everUsedCount = (clone $employeesQuery)
            ->whereHas('appointments', fn ($q) => $q
                ->where('company_id', $tenant->id)
                ->when($userId, fn ($q) => $q->where('user_id', $userId))
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

    private function volumeVariationStat(string $tenantId, ?string $userId): ?Stat
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $durationDays = (int) $start->diffInDays($end);
        $prevEnd = $start->copy()->subDay();
        $prevStart = $prevEnd->copy()->subDays($durationDays);

        $currentTotal = Appointment::query()
            ->where('company_id', $tenantId)
            ->whereBetween('appointment_at', [$start, $end])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->count();

        $previousTotal = Appointment::query()
            ->where('company_id', $tenantId)
            ->whereBetween('appointment_at', [$prevStart, $prevEnd])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
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

    private function topUserStat(Company $tenant): ?Stat
    {
        ['start' => $start, 'end' => $end] = $this->dateRange();

        $topData = Appointment::query()
            ->where('company_id', $tenant->id)
            ->whereBetween('appointment_at', [$start, $end])
            ->selectRaw('user_id, count(*) as period_count')
            ->groupBy('user_id')
            ->orderByDesc('period_count')
            ->toBase()
            ->first();

        if (! $topData) {
            return null;
        }

        $topUser = $tenant->employees()
            ->where('users.id', $topData->user_id)
            ->first();

        if (! $topUser) {
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

    private function dateRange(): array
    {
        $startDate = data_get($this->filters, 'startDate');
        $endDate = data_get($this->filters, 'endDate');

        return [
            'start' => filled($startDate) ? now()->parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay(),
            'end' => filled($endDate) ? now()->parse($endDate)->endOfDay() : now()->endOfDay(),
        ];
    }
}
