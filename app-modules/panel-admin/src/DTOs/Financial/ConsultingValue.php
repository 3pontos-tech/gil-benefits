<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use Illuminate\Support\Collection;

/**
 * Valor das consultorias consumidas no mês, por empresa (STORY-239).
 *
 * A story pedia margem operacional, e a tela não mostra margem: a plataforma
 * não sabe quanto uma consultoria é vendida — a do plano está embutida na
 * mensalidade e não é alocável. O que ela pode saber é quanto uma consultoria
 * vale, que é um número único configurado pela Flamma, e multiplicá-lo pelo
 * volume que ela conhece de verdade.
 */
final readonly class ConsultingValue
{
    /**
     * @param  Collection<int, ConsultingValueRow>  $rows
     */
    public function __construct(
        public Collection $rows,
        public ?int $unitValueCents,
    ) {}

    /**
     * `null` enquanto o valor da consultoria não estiver configurado — a tela
     * avisa em vez de exibir zero.
     */
    public function totalCents(): ?int
    {
        if ($this->unitValueCents === null) {
            return null;
        }

        return $this->billableAppointments() * $this->unitValueCents;
    }

    public function billableAppointments(): int
    {
        return (int) $this->rows->sum(fn (ConsultingValueRow $row): int => $row->billable());
    }

    public function isConfigured(): bool
    {
        return $this->unitValueCents !== null;
    }

    /**
     * Empresas que consumiram consultoria sem ter mensalidade cadastrada.
     *
     * A tela precisa dizer quantas são: sem os dois lados, a comparação entre
     * consumo e mensalidade fica muda justamente onde ela seria mais útil.
     *
     * @return Collection<int, ConsultingValueRow>
     */
    public function withoutMonthlyValue(): Collection
    {
        return $this->rows->reject(
            fn (ConsultingValueRow $row): bool => $row->monthlyValue->isKnown(),
        )->values();
    }
}
