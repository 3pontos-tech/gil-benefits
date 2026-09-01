<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\DTOs\Financial;

use TresPontosTech\PanelAdmin\Enums\ChurnRiskLevel;

/**
 * Uma empresa na lista de risco de churn (STORY-235).
 */
final readonly class ChurnRiskRow
{
    public function __construct(
        public string $companyId,
        public string $companyName,
        public float $usageRate,
        public int $monthlyValueCents,
        public int $registered,
        public int $withCompletedAppointment,
        public ChurnRiskLevel $level,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'company_name' => $this->companyName,
            'usage_rate' => $this->usageRate,
            'monthly_value_cents' => $this->monthlyValueCents,
            'registered' => $this->registered,
            'with_completed' => $this->withCompletedAppointment,
            'level' => $this->level->value,
        ];
    }
}
