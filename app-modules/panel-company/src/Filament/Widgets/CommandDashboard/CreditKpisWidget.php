<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\CommandDashboard;

use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetCreditSeries;
use TresPontosTech\PanelCompany\Actions\Metrics\GetCreditTotals;
use TresPontosTech\PanelCompany\DTOs\CreditKpi;
use TresPontosTech\PanelCompany\Support\ChartGeometry;
use TresPontosTech\PanelCompany\Support\MetricsPeriod;

class CreditKpisWidget extends Widget
{
    protected static bool $isDiscovered = false;

    protected string $view = 'panel-company::filament.widgets.command-dashboard.credit-kpis';

    protected int|string|array $columnSpan = 7;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();
        $period = MetricsPeriod::lastMonths(12);
        $totals = resolve(GetCreditTotals::class)->handle($tenant);
        $series = resolve(GetCreditSeries::class);

        /** @var array<string, string> $labels */
        $labels = trans('panel-company::resources.pages.command_dashboard.credits');

        $kpis = [
            $this->kpi($labels['total'], $totals->total, $labels['total_caption'], 'primary', $series->handle($tenant, $period)),
            $this->kpi($labels['available'], $totals->available, $labels['available_caption'], 'success', $series->handle($tenant, $period, UserCreditStatusEnum::Available->value)),
            $this->kpi($labels['in_use'], $totals->inUse, $labels['in_use_caption'], 'info', $series->handle($tenant, $period, UserCreditStatusEnum::InUse->value)),
            $this->kpi($labels['used'], $totals->used, $labels['used_caption'], 'neutral', $series->handle($tenant, $period, UserCreditStatusEnum::Used->value)),
        ];

        return ['kpis' => $kpis];
    }

    /**
     * @param  array<int, int>  $series
     */
    private function kpi(string $label, int $value, string $caption, string $tone, array $series): CreditKpi
    {
        return new CreditKpi(
            label: $label,
            value: $value,
            caption: $caption,
            tone: $tone,
            sparkline: ChartGeometry::sparkline($series),
        );
    }
}
