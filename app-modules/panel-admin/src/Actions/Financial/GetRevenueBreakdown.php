<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Actions\Financial;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use TresPontosTech\PanelAdmin\Actions\Financial\Concerns\BuildsFinancialCacheKey;
use TresPontosTech\PanelAdmin\DTOs\Financial\ContractRow;
use TresPontosTech\PanelAdmin\DTOs\Financial\FinancialFilters;
use TresPontosTech\PanelAdmin\DTOs\Financial\RevenueBreakdown;

/**
 * Receita por plano e ranking de empresas (STORY-232).
 *
 * Reaproveita a listagem da STORY-234, que já resolve valor e plano por empresa
 * com a precedência correta. Recalcular aqui abriria a porta para o ranking e a
 * tabela discordarem sobre quanto a mesma empresa paga.
 */
final class GetRevenueBreakdown
{
    use BuildsFinancialCacheKey;

    private const string BUCKET = 'revenue_breakdown';

    public function __construct(private readonly GetContractsTable $contracts) {}

    public function handle(FinancialFilters $filters, ?CarbonImmutable $now = null): RevenueBreakdown
    {
        return Cache::remember(
            $this->financialCacheKey(self::BUCKET, $filters),
            $this->financialCacheTtl(),
            fn (): RevenueBreakdown => $this->build($filters, $now),
        );
    }

    public function forget(FinancialFilters $filters): void
    {
        $this->forgetFinancialCache(self::BUCKET, $filters);
    }

    private function build(FinancialFilters $filters, ?CarbonImmutable $now): RevenueBreakdown
    {
        $rows = $this->contracts->handle($filters, $now)
            ->filter(fn (ContractRow $row): bool => $row->monthlyValue->isKnown())
            ->sortByDesc(fn (ContractRow $row): int => (int) $row->monthlyValue->cents)
            ->values();

        $byPlan = [];

        foreach ($rows as $row) {
            $plan = $row->planName ?? __('panel-admin::widgets.financial.contracts.no_plan');
            $byPlan[$plan] = ($byPlan[$plan] ?? 0) + (int) $row->monthlyValue->cents;
        }

        arsort($byPlan);

        return new RevenueBreakdown(
            byPlan: $byPlan,
            ranking: $rows,
            totalCents: (int) $rows->sum(fn (ContractRow $row): int => (int) $row->monthlyValue->cents),
        );
    }
}
