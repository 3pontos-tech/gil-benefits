<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

/**
 * Consumo de crédito de uma empresa no mês, por origem (STORY-240).
 *
 * As três colunas são medidas pelo mesmo relógio — o consumo, isto é, a data da
 * consultoria que gastou o crédito. Misturar consumo com data de pagamento faria
 * a linha se contradizer: um crédito comprado em julho e usado em agosto
 * apareceria como uso sem valor.
 */
final readonly class ExtraCreditsRow
{
    public function __construct(
        public string $companyId,
        public string $companyName,
        public int $fromPlan,
        public int $purchased,
        public int $granted,
        public int $purchasedValueCents,
    ) {}

    public function total(): int
    {
        return $this->fromPlan + $this->purchased + $this->granted;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'company_name' => $this->companyName,
            'from_plan' => $this->fromPlan,
            'purchased' => $this->purchased,
            'granted' => $this->granted,
            'purchased_value_cents' => $this->purchasedValueCents,
        ];
    }
}
