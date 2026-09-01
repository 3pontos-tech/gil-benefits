<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Financial;

use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Collection;
use TresPontosTech\PanelAdmin\Actions\Financial\GetRevenueSeries;
use TresPontosTech\PanelAdmin\DTOs\Financial\RevenueSeriesPoint;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;

/**
 * Evolução da receita mês a mês (STORY-231).
 *
 * Todo ponto anterior ao mês corrente é reconstruído por vigência com o preço de
 * hoje (D-02), e o subtítulo diz isso: sem esse aviso, o gráfico seria lido como
 * extrato histórico, quando upgrades e downgrades passados são invisíveis nele.
 */
class RevenueSeriesChartWidget extends ChartWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public ?string $filter = '12';

    public function getHeading(): ?string
    {
        return __('panel-admin::widgets.financial.series.heading');
    }

    public function getDescription(): ?string
    {
        return __('panel-admin::widgets.financial.series.description');
    }

    protected function getFilters(): ?array
    {
        return [
            '3' => __('panel-admin::widgets.financial.series.filter_3'),
            '6' => __('panel-admin::widgets.financial.series.filter_6'),
            '12' => __('panel-admin::widgets.financial.series.filter_12'),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getData(): array
    {
        $points = $this->points();

        return [
            'datasets' => [
                [
                    'label' => __('panel-admin::widgets.financial.series.dataset'),
                    'data' => $points->map(fn (RevenueSeriesPoint $point): float => round($point->totalCents / 100, 2))->all(),
                    'tension' => 0.3,
                    'borderColor' => 'rgb(16, 122, 87)',
                    'backgroundColor' => 'rgba(16, 122, 87, 0.12)',
                    'fill' => true,
                ],
            ],
            'labels' => $points->map(fn (RevenueSeriesPoint $point): string => $point->label)->all(),
        ];
    }

    /**
     * Tooltip com mês, receita e variação, como o cenário 3 da story pede.
     *
     * As variações vão embutidas como um vetor de números ao lado da série: o
     * Chart.js só recebe os valores do eixo, e a variação é um dado à parte que
     * precisa acompanhar o índice do ponto.
     */
    protected function getOptions(): RawJs|array|null
    {
        $variations = $this->points()
            ->map(fn (RevenueSeriesPoint $point): ?float => $point->variation)
            ->all();

        $suffix = __('panel-admin::widgets.financial.series.tooltip_vs_previous');

        return RawJs::make(sprintf(<<<'JS'
            {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => new Intl.NumberFormat('pt-BR', {
                                style: 'currency', currency: 'BRL', maximumFractionDigits: 0,
                            }).format(value),
                        },
                    },
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const variations = %s;
                                const money = new Intl.NumberFormat('pt-BR', {
                                    style: 'currency', currency: 'BRL',
                                }).format(context.parsed.y);
                                const variation = variations[context.dataIndex];

                                if (variation === null || variation === undefined) {
                                    return money;
                                }

                                const sign = variation >= 0 ? '+' : '';

                                return money + ' (' + sign + variation.toFixed(1) + '%% %s)';
                            },
                        },
                    },
                },
            }
            JS, json_encode(array_values($variations)), $suffix));
    }

    /**
     * @return Collection<int, RevenueSeriesPoint>
     */
    private function points(): Collection
    {
        return resolve(GetRevenueSeries::class)->handle(
            $this->financialFilters(),
            (int) ($this->filter ?? 12),
        );
    }
}
