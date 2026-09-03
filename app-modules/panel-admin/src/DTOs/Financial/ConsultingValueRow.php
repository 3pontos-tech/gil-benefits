<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use TresPontosTech\Billing\Core\DTOs\MonthlyValue;

/**
 * Consultorias consumidas por uma empresa no mês, e quanto valem (STORY-239).
 *
 * A mensalidade viaja junto porque é a comparação que o número serve para
 * fazer: quanto a empresa consumiu contra quanto ela paga. Sozinho, o valor
 * consumido não responde nada.
 */
final readonly class ConsultingValueRow
{
    public function __construct(
        public string $companyId,
        public string $companyName,
        public int $completed,
        public int $cancelledLate,
        public int $noShow,
        public MonthlyValue $monthlyValue,
        public ?int $unitValueCents,
    ) {}

    /**
     * Consultorias que consumiram crédito do cliente.
     *
     * A régua é a do PO: realizadas, mais os desfechos que fazem o cliente
     * perder o crédito — cancelamento fora da regra e não comparecimento.
     * Confere com o código: o crédito é consumido na confirmação e só volta
     * em cancelamento dentro da regra.
     */
    public function billable(): int
    {
        return $this->completed + $this->cancelledLate + $this->noShow;
    }

    /**
     * `null` enquanto o valor da consultoria não estiver configurado.
     * Zero diria que a consultoria não vale nada.
     */
    public function valueCents(): ?int
    {
        if ($this->unitValueCents === null) {
            return null;
        }

        return $this->billable() * $this->unitValueCents;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'company_name' => $this->companyName,
            'completed' => $this->completed,
            'cancelled_late' => $this->cancelledLate,
            'no_show' => $this->noShow,
            'billable' => $this->billable(),
            'value_cents' => $this->valueCents(),
            'monthly_value_cents' => $this->monthlyValue->isKnown() ? $this->monthlyValue->cents : null,
        ];
    }
}
