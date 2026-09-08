<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Actions;

use TresPontosTech\Company\Models\Company;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

class RevokeCreditsFromEmployees
{
    public function handle(Company $company): void
    {
        UserCredit::query()
            ->where('company_id', $company->getKey())
            ->where('owner_id', $company->user_id)
            ->where('holder_id', '!=', $company->user_id)
            ->where('status', UserCreditStatusEnum::Available)
            ->update([
                'holder_id' => $company->user_id,
                'transferred_at' => null,
            ]);
    }
}
