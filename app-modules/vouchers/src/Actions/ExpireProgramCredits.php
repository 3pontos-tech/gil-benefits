<?php

declare(strict_types=1);

namespace TresPontosTech\Vouchers\Actions;

use TresPontosTech\Billing\Core\Models\CompanyPlan;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;
use TresPontosTech\Vouchers\Models\VoucherRedemption;

final readonly class ExpireProgramCredits
{
    public function handle(CompanyPlan $plan): int
    {
        $redemptionIds = VoucherRedemption::query()
            ->whereHas('code.batch', fn ($query) => $query->where('company_plan_id', $plan->getKey()))
            ->pluck('id');

        if ($redemptionIds->isEmpty()) {
            return 0;
        }

        return UserCredit::query()
            ->whereIn('voucher_redemption_id', $redemptionIds)
            ->where('status', UserCreditStatusEnum::Available)
            ->update(['status' => UserCreditStatusEnum::Expired]);
    }
}
