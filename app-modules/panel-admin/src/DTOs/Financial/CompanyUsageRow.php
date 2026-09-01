<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use TresPontosTech\PanelAdmin\Support\EngagementNumber;

/**
 * Utilização de uma empresa (STORY-241).
 *
 * "Utilizaram" é do período; "Nunca Utilizaram" é all-time — quem nunca usou em
 * agosto pode ter usado em março, e chamar isso de "nunca" seria falso.
 */
final readonly class CompanyUsageRow
{
    public function __construct(
        public string $companyId,
        public string $companyName,
        public int $seats,
        public int $registered,
        public int $usedInPeriod,
        public int $neverUsed,
    ) {}

    /**
     * Fatia da base cadastrada que nunca usou o benefício.
     *
     * O denominador é `registered`, não `seats`: baixa adesão de cadastro é
     * outro problema, já coberto pelo relatório de engajamento, e misturar os
     * dois no mesmo destaque esconderia a causa.
     */
    public function neverUsedRate(): ?float
    {
        return EngagementNumber::rate($this->neverUsed, $this->registered);
    }

    public function usageRate(): ?float
    {
        return EngagementNumber::rate($this->usedInPeriod, $this->registered);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'company_name' => $this->companyName,
            'seats' => $this->seats,
            'registered' => $this->registered,
            'used_in_period' => $this->usedInPeriod,
            'never_used' => $this->neverUsed,
            'never_used_rate' => $this->neverUsedRate(),
        ];
    }
}
