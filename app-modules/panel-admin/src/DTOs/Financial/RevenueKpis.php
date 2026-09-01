<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use Carbon\CarbonImmutable;

/**
 * Os KPIs de receita do mês na tela (STORY-230).
 *
 * A story pedia 6 cards; "Receita Projetada" saiu por D-18 — o cockpit não
 * exibe dinheiro que ainda não aconteceu. Restam 5.
 */
final readonly class RevenueKpis
{
    public function __construct(
        public MonthlyRevenue $current,
        public ?MonthlyRevenue $previous,
        public int $extraCreditsCents,
        public CarbonImmutable $generatedAt,
    ) {}

    /**
     * Receita total do mês: a mensalidade vigente somada aos créditos extras
     * efetivamente pagos no período.
     */
    public function totalRevenueCents(): int
    {
        return $this->current->totalCents() + $this->extraCreditsCents;
    }

    public function variation(string $metric): ?float
    {
        return $this->current->variationAgainst($this->previous, $metric);
    }

    /**
     * Se há mês anterior reconstruível para comparar.
     */
    public function hasComparison(): bool
    {
        return $this->previous instanceof MonthlyRevenue;
    }
}
