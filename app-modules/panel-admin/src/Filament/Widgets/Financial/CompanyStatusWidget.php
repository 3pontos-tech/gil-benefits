<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Filament\Widgets\Financial;

use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;
use TresPontosTech\PanelAdmin\Actions\Financial\GetCompanyStatusTotals;
use TresPontosTech\PanelAdmin\Filament\Pages\Financial\CompaniesAndContracts;
use TresPontosTech\PanelAdmin\Filament\Widgets\Financial\Concerns\HasFinancialFilters;
use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Cards de empresas por status financeiro (STORY-233).
 *
 * Cada card leva para a listagem já filtrada, atendendo o cenário "Navegação por
 * status" da story. O card de renovação não filtra por status: ele é uma janela
 * de tempo sobre a base viva, e por isso usa o parâmetro próprio.
 */
class CompanyStatusWidget extends StatsOverviewWidget
{
    use HasFinancialFilters;
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 0;

    protected function getHeading(): ?string
    {
        return __('panel-admin::widgets.financial.company_status.heading');
    }

    protected function getDescription(): ?string
    {
        return __('panel-admin::widgets.financial.company_status.description');
    }

    protected function getColumns(): int
    {
        return 5;
    }

    protected function getStats(): array
    {
        $totals = resolve(GetCompanyStatusTotals::class)->handle($this->financialFilters());

        $stats = [];

        foreach ([
            CompanyFinancialStatusEnum::Active,
            CompanyFinancialStatusEnum::Trial,
            CompanyFinancialStatusEnum::Delinquent,
            CompanyFinancialStatusEnum::Cancelled,
        ] as $status) {
            $stats[] = Stat::make($status->getLabel(), EngagementNumber::integer($totals->count($status)))
                ->description($this->descriptionFor($status))
                ->descriptionIcon($status->getIcon())
                ->color($this->colorFor($status))
                ->url(CompaniesAndContracts::getUrl(['status' => $status->value]));
        }

        $stats[] = Stat::make(
            __('panel-admin::widgets.financial.company_status.renewing'),
            EngagementNumber::integer($totals->renewingIn30Days),
        )
            ->description($this->renewingDescription($totals->renewingIn7Days))
            ->descriptionIcon('heroicon-o-calendar-days')
            ->color($totals->renewingIn7Days > 0 ? 'warning' : 'gray')
            ->url(CompaniesAndContracts::getUrl(['renewing' => 30]));

        return $stats;
    }

    /**
     * O card "Em Trial" carrega um aviso mesmo zerado: hoje não existe fluxo de
     * trial B2B, e um zero sem contexto é lido como bug pelo financeiro.
     */
    private function descriptionFor(CompanyFinancialStatusEnum $status): string
    {
        return __('panel-admin::widgets.financial.company_status.' . $status->value . '_description');
    }

    private function colorFor(CompanyFinancialStatusEnum $status): string
    {
        return match ($status) {
            CompanyFinancialStatusEnum::Active => 'success',
            CompanyFinancialStatusEnum::Trial => 'info',
            CompanyFinancialStatusEnum::Delinquent => 'warning',
            CompanyFinancialStatusEnum::Cancelled => 'danger',
            CompanyFinancialStatusEnum::None => 'gray',
        };
    }

    private function renewingDescription(int $inSevenDays): string
    {
        if ($inSevenDays < 1) {
            return __('panel-admin::widgets.financial.company_status.renewing_description');
        }

        return trans_choice(
            'panel-admin::widgets.financial.company_status.renewing_soon',
            $inSevenDays,
            ['total' => EngagementNumber::integer($inSevenDays)],
        );
    }
}
