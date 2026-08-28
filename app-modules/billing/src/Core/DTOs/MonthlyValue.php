<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\DTOs;

use TresPontosTech\Billing\Core\Enums\MonthlyValueSourceEnum;

/**
 * Quanto um pagante vale por mês, e de onde esse número saiu (FLM-41, D-01).
 *
 * Só existe valor para o que foi efetivamente cobrado. O cockpit não estima
 * dinheiro: `null` e zero são coisas diferentes, e um contrato sem preço é uma
 * empresa pagante cujo valor a Flamma não sabe — exibir R$ 0 ali, ou um palpite
 * pela tabela, seria afirmar algo falso sobre a receita.
 */
final readonly class MonthlyValue
{
    private function __construct(
        public ?int $cents,
        public MonthlyValueSourceEnum $source,
    ) {}

    public static function charged(int $cents, MonthlyValueSourceEnum $source): self
    {
        return new self($cents, $source);
    }

    public static function unknown(): self
    {
        return new self(null, MonthlyValueSourceEnum::Unknown);
    }

    public function isKnown(): bool
    {
        return $this->cents !== null;
    }
}
