<?php

declare(strict_types=1);

namespace TresPontosTech\PanelCompany\Filament\Widgets\Metrics;

use Filament\Facades\Filament;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Actions\Metrics\GetDepartmentVolume;
use TresPontosTech\PanelCompany\DTOs\DepartmentVolumeRow;
use TresPontosTech\PanelCompany\Filament\Concerns\HasMetricsDateRange;

class DepartmentVolumeChart extends ChartWidget
{
    use HasMetricsDateRange;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 6;

    public function getHeading(): ?string
    {
        return __('panel-company::widgets.appointments_by_department.heading');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        /** @var Company $tenant */
        $tenant = Filament::getTenant();

        $volume = resolve(GetDepartmentVolume::class)->handle($tenant, $this->metricsPeriod());
        $selectedDepartmentId = data_get($this->pageFilters, 'departmentId');

        $colors = array_map(
            fn (DepartmentVolumeRow $row): string => filled($selectedDepartmentId) && $row->id === (string) $selectedDepartmentId
                ? 'rgba(139, 92, 246, 0.9)'
                : 'rgba(59, 130, 246, 0.7)',
            $volume->rows,
        );

        return [
            'datasets' => [
                [
                    'label' => __('panel-company::widgets.appointments_by_department.dataset_label'),
                    'data' => array_map(fn (DepartmentVolumeRow $row): int => $row->total, $volume->rows),
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => array_map(fn (DepartmentVolumeRow $row): string => $row->name, $volume->rows),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['display' => false]]];
    }
}
