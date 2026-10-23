<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Enums\Employee\Auth;

use TresPontosTech\Billing\Core\Enums\BenefitAccessEnum;

enum AppAccessDenial: string
{
    /** Consultor, ou sem vínculo ativo com empresa nenhuma: o app não é para essa conta. */
    case NotAudience = 'not_audience';

    case CompanyPlanInactive = 'company_plan_inactive';

    case PersonalPlanRequired = 'personal_plan_required';

    public static function fromBenefit(BenefitAccessEnum $benefit): self
    {
        return $benefit === BenefitAccessEnum::CompanyPlanInactive ? self::CompanyPlanInactive : self::PersonalPlanRequired;
    }

    public function message(): string
    {
        return match ($this) {
            self::NotAudience => __('api::auth.no_access'),
            self::CompanyPlanInactive => __('api::auth.company_plan_inactive'),
            self::PersonalPlanRequired => __('api::auth.personal_plan_required'),
        };
    }
}
