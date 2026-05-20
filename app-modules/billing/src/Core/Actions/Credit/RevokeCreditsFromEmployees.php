<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions\Credit;

use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;

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
