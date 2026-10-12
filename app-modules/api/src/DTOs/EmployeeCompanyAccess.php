<?php

declare(strict_types=1);

namespace TresPontosTech\Api\DTOs;

use TresPontosTech\Billing\Core\Enums\BenefitAccessEnum;
use TresPontosTech\Company\Models\Company;

/**
 * A empresa sob a qual o app funciona para esta pessoa, ou por que nenhuma serve.
 *
 * `denial` é o motivo da empresa empregadora (a primeira da ordem); fica nulo quando a pessoa
 * não tem vínculo ativo nenhum.
 */
final readonly class EmployeeCompanyAccess
{
    public function __construct(
        public ?Company $company,
        public ?BenefitAccessEnum $denial = null,
    ) {}

    public function isGranted(): bool
    {
        return $this->company instanceof Company;
    }

    public function denialMessage(): string
    {
        return match ($this->denial) {
            BenefitAccessEnum::CompanyPlanInactive => __('api::auth.company_plan_inactive'),
            BenefitAccessEnum::PersonalPlanRequired => __('api::auth.personal_plan_required'),
            default => __('api::auth.no_access'),
        };
    }
}
