<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use Illuminate\Support\Collection;

/**
 * Repasse do mês, consolidado e por consultor (STORY-239).
 *
 * A tela não mostra margem: a plataforma não sabe quanto vale uma consultoria —
 * a do plano está embutida na mensalidade e não é alocável. O que ela sabe, e
 * mostra, é quanto foi repassado. Quem tem o número de receita faz a conta por
 * fora, e quando os valores de contrato existirem a margem passa a ser
 * calculável sem retrabalho.
 */
final readonly class ConsultingPayout
{
    /**
     * @param  Collection<int, PayoutRow>  $rows
     */
    public function __construct(
        public Collection $rows,
        public ?int $defaultCostCents,
    ) {}

    /**
     * Total repassado, contando só quem tem custo definido.
     */
    public function totalCents(): int
    {
        return (int) $this->rows
            ->filter(fn (PayoutRow $row): bool => $row->hasCost())
            ->sum(fn (PayoutRow $row): int => (int) $row->payoutCents());
    }

    public function billableAppointments(): int
    {
        return (int) $this->rows->sum(fn (PayoutRow $row): int => $row->billable());
    }

    /**
     * Consultores que atenderam no mês mas ficaram fora do total por não ter
     * custo definido. A tela precisa dizer isso: o total estaria incompleto e
     * ninguém perceberia.
     *
     * @return Collection<int, PayoutRow>
     */
    public function withoutCost(): Collection
    {
        return $this->rows->filter(
            fn (PayoutRow $row): bool => ! $row->hasCost() && $row->billable() > 0,
        )->values();
    }

    public function isConfigured(): bool
    {
        return $this->defaultCostCents !== null || $this->rows->contains(fn (PayoutRow $row): bool => $row->hasCost());
    }
}
