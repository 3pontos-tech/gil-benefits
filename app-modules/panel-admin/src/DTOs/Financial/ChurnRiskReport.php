<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use Illuminate\Support\Collection;

/**
 * Resultado da análise de risco de churn (STORY-235).
 *
 * Carrega, além das linhas, o que foi deixado de fora e por quê: a mediana só
 * pode ser comparada com quem tem valor conhecido, e uma lista curta sem
 * explicação faz o CS achar que a base está saudável quando na verdade metade
 * dela não pôde ser avaliada.
 */
final readonly class ChurnRiskReport
{
    /**
     * @param  Collection<int, ChurnRiskRow>  $rows
     */
    public function __construct(
        public Collection $rows,
        public int $medianValueCents,
        public int $companiesWithoutValue,
        public int $companiesEvaluated,
    ) {}

    public function isEmpty(): bool
    {
        return $this->rows->isEmpty();
    }

    /**
     * Soma do que está em risco por mês — o número que justifica a priorização.
     */
    public function valueAtRiskCents(): int
    {
        return (int) $this->rows->sum(fn (ChurnRiskRow $row): int => $row->monthlyValueCents);
    }
}
