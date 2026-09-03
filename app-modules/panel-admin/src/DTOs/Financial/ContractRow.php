<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use Carbon\CarbonImmutable;
use TresPontosTech\Billing\Core\DTOs\MonthlyValue;
use TresPontosTech\Billing\Core\Enums\CompanyFinancialStatusEnum;

/**
 * Uma linha da listagem de empresas e contratos (STORY-234).
 */
final readonly class ContractRow
{
    public function __construct(
        public string $companyId,
        public string $companyName,
        public ?string $planName,
        public MonthlyValue $monthlyValue,
        public ?CarbonImmutable $nextChargeAt,
        public CompanyFinancialStatusEnum $status,
    ) {}

    /**
     * Formato de array usado pela tabela e pelo CSV.
     *
     * A tabela do Filament ordena e busca sobre estes valores crus; a formatação
     * fica nas colunas, para ordenar por valor e não por texto formatado.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'company_name' => $this->companyName,
            'plan_name' => $this->planName,
            'monthly_value_cents' => $this->monthlyValue->cents,
            'next_charge_at' => $this->nextChargeAt?->toDateString(),
            'status' => $this->status->value,
        ];
    }
}
