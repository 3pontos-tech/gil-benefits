<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Financial;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use TresPontosTech\PanelAdmin\Actions\Financial\GetRevenueBreakdown;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;

/**
 * Participação de cada plano na receita B2B do mês (STORY-232).
 */
class RevenueByPlanChartWidget extends ChartWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 2;

    /**
     * Paleta fixa e ordenada: o plano maior fica sempre com a mesma cor entre
     * um carregamento e outro, para a leitura não mudar de significado.
     *
     * @var array<int, string>
     */
    private const array COLORS = [
        'rgb(16, 122, 87)',
        'rgb(37, 99, 235)',
        'rgb(217, 119, 6)',
        'rgb(147, 51, 234)',
        'rgb(190, 24, 93)',
        'rgb(100, 116, 139)',
    ];

    public function getHeading(): ?string
    {
        return __('panel-admin::widgets.financial.by_plan.heading');
    }

    public function getDescription(): ?string
    {
        return __('panel-admin::widgets.financial.by_plan.description');
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $byPlan = resolve(GetRevenueBreakdown::class)->handle($this->financialFilters())->byPlan;

        return [
            'datasets' => [
                [
                    'label' => __('panel-admin::widgets.financial.by_plan.dataset'),
                    'data' => array_map(
                        static fn (int $cents): float => round($cents / 100, 2),
                        array_values($byPlan),
                    ),
                    'backgroundColor' => $this->colorsFor(count($byPlan)),
                ],
            ],
            'labels' => array_keys($byPlan),
        ];
    }

    /**
     * Cores suficientes para os planos existentes, repetindo a última quando
     * houver mais planos do que cores.
     *
     * @return array<int, string>
     */
    private function colorsFor(int $slices): array
    {
        $colors = self::COLORS;
        $fallback = $colors[count($colors) - 1];

        return array_slice(array_pad($colors, max($slices, 1), $fallback), 0, max($slices, 1));
    }
}
