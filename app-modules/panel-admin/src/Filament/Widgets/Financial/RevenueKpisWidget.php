<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Financial;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Billing\Core\Support\MoneyCents;
use TresPontosTech\PanelAdmin\Actions\Financial\GetRevenueKpis;
use TresPontosTech\PanelAdmin\DTOs\Financial\RevenueKpis;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Os cards de receita do mês (STORY-230).
 *
 * Cinco cards, não seis: "Receita Projetada" foi cortada por D-18. O rodapé diz
 * a hora do cálculo, porque com cache de 5 minutos o número na tela pode não ser
 * o do instante em que se olha (D-12).
 */
class RevenueKpisWidget extends StatsOverviewWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected function getHeading(): ?string
    {
        return __('panel-admin::widgets.financial.revenue.heading');
    }

    protected function getDescription(): ?string
    {
        $kpis = $this->kpis();

        return __('panel-admin::widgets.financial.revenue.description', [
            'time' => $kpis->generatedAt->format('H:i'),
        ]);
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected function getStats(): array
    {
        $kpis = $this->kpis();
        $current = $kpis->current;

        return [
            $this->money(
                __('panel-admin::widgets.financial.revenue.mrr'),
                $current->totalCents(),
                $kpis->variation('total'),
                __('panel-admin::widgets.financial.revenue.mrr_split', [
                    'b2b' => MoneyCents::fromCents($current->b2bCents)->format(),
                    'standalone' => MoneyCents::fromCents($current->standaloneCents)->format(),
                ]),
                'heroicon-o-arrow-trending-up',
            ),

            $this->money(
                __('panel-admin::widgets.financial.revenue.total'),
                $kpis->totalRevenueCents(),
                null,
                __('panel-admin::widgets.financial.revenue.total_description', [
                    'extras' => MoneyCents::fromCents($kpis->extraCreditsCents)->format(),
                ]),
                'heroicon-o-banknotes',
            ),

            $this->ticketStat($kpis),

            $this->count(
                __('panel-admin::widgets.financial.revenue.paying_companies'),
                $current->payingCompanies,
                $kpis->variation('paying_companies'),
                __('panel-admin::widgets.financial.revenue.paying_companies_description'),
                'heroicon-o-building-office-2',
            ),

            $this->count(
                __('panel-admin::widgets.financial.revenue.paying_users'),
                $current->payingUsers,
                $kpis->variation('paying_users'),
                __('panel-admin::widgets.financial.revenue.paying_users_description'),
                'heroicon-o-users',
            ),
        ];
    }

    /**
     * O ticket médio some quando nenhuma empresa tem valor conhecido: exibir
     * zero afirmaria que o cliente médio não paga nada (D-01).
     */
    private function ticketStat(RevenueKpis $kpis): Stat
    {
        $ticket = $kpis->current->averageTicketCents();

        if ($ticket === null) {
            return Stat::make(
                __('panel-admin::widgets.financial.revenue.average_ticket'),
                EngagementNumber::EMPTY,
            )
                ->description(__('panel-admin::widgets.financial.revenue.average_ticket_unknown'))
                ->descriptionIcon('heroicon-o-receipt-percent')
                ->color('gray');
        }

        return $this->money(
            __('panel-admin::widgets.financial.revenue.average_ticket'),
            $ticket,
            $kpis->variation('average_ticket'),
            __('panel-admin::widgets.financial.revenue.average_ticket_description', [
                'companies' => EngagementNumber::integer($kpis->current->companiesWithKnownValue),
            ]),
            'heroicon-o-receipt-percent',
        );
    }

    private function money(string $label, int $cents, ?float $variation, string $description, string $icon): Stat
    {
        return $this->decorate(
            Stat::make($label, MoneyCents::fromCents($cents)->format()),
            $variation,
            $description,
            $icon,
        );
    }

    private function count(string $label, int $value, ?float $variation, string $description, string $icon): Stat
    {
        return $this->decorate(
            Stat::make($label, EngagementNumber::integer($value)),
            $variation,
            $description,
            $icon,
        );
    }

    /**
     * Sem mês anterior comparável, o card fica cinza e sem seta — nunca um 0%
     * fabricado, que seria lido como estabilidade (STORY-230, cenário 3).
     */
    private function decorate(Stat $stat, ?float $variation, string $description, string $icon): Stat
    {
        if ($variation === null) {
            return $stat->description($description)->descriptionIcon($icon)->color('gray');
        }

        return $stat
            ->description(__('panel-admin::widgets.financial.revenue.variation', [
                'value' => EngagementNumber::percent(abs($variation)),
                'detail' => $description,
            ]))
            ->descriptionIcon($variation >= 0 ? 'heroicon-o-arrow-trending-up' : 'heroicon-o-arrow-trending-down')
            ->color($variation >= 0 ? 'success' : 'danger');
    }

    private function kpis(): RevenueKpis
    {
        return resolve(GetRevenueKpis::class)->handle($this->financialFilters());
    }
}
