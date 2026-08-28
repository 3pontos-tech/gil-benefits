<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use Illuminate\Support\Collection;
use TresPontosTech\PanelAdmin\Support\FinancialThresholds;

/**
 * Distribuição da receita B2B por plano e por empresa (STORY-232).
 *
 * Só B2B (D-11): a empresa-balde dos avulsos ficaria eternamente no topo do
 * ranking e dispararia o alerta de concentração todo mês sem significar nada.
 */
final readonly class RevenueBreakdown
{
    /**
     * @param  array<string, int>  $byPlan  Receita por nome de plano, em centavos.
     * @param  Collection<int, ContractRow>  $ranking  Empresas da maior para a menor.
     */
    public function __construct(
        public array $byPlan,
        public Collection $ranking,
        public int $totalCents,
    ) {}

    /**
     * Participação percentual de um plano na receita do mês.
     */
    public function planShare(string $plan): ?float
    {
        if ($this->totalCents < 1) {
            return null;
        }

        return round((($this->byPlan[$plan] ?? 0) / $this->totalCents) * 100, 1);
    }

    public function topCompany(): ?ContractRow
    {
        return $this->ranking->first();
    }

    /**
     * Quanto a maior empresa representa da receita B2B.
     */
    public function concentrationRate(): ?float
    {
        $top = $this->topCompany();

        if (! $top instanceof ContractRow || $this->totalCents < 1 || $top->monthlyValue->cents === null) {
            return null;
        }

        return round(($top->monthlyValue->cents / $this->totalCents) * 100, 1);
    }

    /**
     * Alerta de dependência de um único cliente.
     *
     * O corte da story é 30%: acima disso, perder um cliente deixa de ser um
     * problema de CS e vira um problema de caixa.
     */
    public function hasConcentrationAlert(): bool
    {
        $rate = $this->concentrationRate();

        return $rate !== null && $rate > FinancialThresholds::REVENUE_CONCENTRATION;
    }
}
