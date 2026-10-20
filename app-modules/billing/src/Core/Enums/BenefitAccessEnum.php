<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Enums;

/**
 * @see ResolveBenefitAccess
 */
enum BenefitAccessEnum: string
{
    /** A empresa tem plano contratual em vigor. */
    case ContractualPlan = 'contractual_plan';

    /** Assinatura própria (ou voucher, na empresa padrão). */
    case PersonalPlan = 'personal_plan';

    /** A empresa não tem plano nem assinatura ativa; o colaborador não resolve sozinho. */
    case CompanyPlanInactive = 'company_plan_inactive';

    /** A empresa está em dia, mas a pessoa ainda não tem plano próprio. */
    case PersonalPlanRequired = 'personal_plan_required';

    public function isGranted(): bool
    {
        return $this === self::ContractualPlan || $this === self::PersonalPlan;
    }
}
