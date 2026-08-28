<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Financial;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\PanelAdmin\Actions\Financial\GetActivationTotals;
use TresPontosTech\PanelAdmin\DTOs\Financial\ActivationTotals;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Utilização agregada da base (STORY-242).
 *
 * A régua de "ativo" vai escrita na descrição do widget, e não só na
 * documentação: é um proxy (D-09), e quem lê o número precisa saber disso sem
 * abrir o plano.
 */
class ActivationTotalsWidget extends StatsOverviewWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected function getHeading(): ?string
    {
        return __('panel-admin::widgets.financial.activation.heading');
    }

    protected function getDescription(): ?string
    {
        return __('panel-admin::widgets.financial.activation.description');
    }

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        ['current' => $current, 'previous' => $previous] = resolve(GetActivationTotals::class)
            ->handle($this->financialFilters());

        return [
            $this->stat('total', $current, $previous, 'heroicon-o-users'),
            $this->stat('active', $current, $previous, 'heroicon-o-check-badge'),
            $this->stat('inactive', $current, $previous, 'heroicon-o-pause-circle'),
            $this->stat('without_access', $current, $previous, 'heroicon-o-lock-closed'),

            Stat::make(
                __('panel-admin::widgets.financial.activation.activation_rate'),
                EngagementNumber::percent($current->activationRate()),
            )
                ->description(__('panel-admin::widgets.financial.activation.activation_rate_description'))
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('gray'),
        ];
    }

    /**
     * Sem mês anterior comparável o card fica cinza e sem seta — a mesma regra
     * dos cards de receita, para as duas telas não contarem histórias
     * diferentes sobre a mesma ausência de dado.
     */
    private function stat(string $metric, ActivationTotals $current, ?ActivationTotals $previous, string $icon): Stat
    {
        $stat = Stat::make(
            __("panel-admin::widgets.financial.activation.{$metric}"),
            EngagementNumber::integer($current->metric($metric)),
        );

        $variation = $current->variationAgainst($previous, $metric);
        $description = __("panel-admin::widgets.financial.activation.{$metric}_description");

        if ($variation === null) {
            return $stat->description($description)->descriptionIcon($icon)->color('gray');
        }

        return $stat
            ->description(__('panel-admin::widgets.financial.activation.variation', [
                'value' => EngagementNumber::percent(abs($variation)),
                'detail' => $description,
            ]))
            ->descriptionIcon($variation >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
            ->color($variation >= 0 ? 'success' : 'danger');
    }
}
