<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions;

use App\Models\Users\User;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;
use TresPontosTech\Company\Models\Company;

class PurchaseCredits
{
    public function handle(User $owner, Company $company, int $quantity): void
    {
        for ($i = 0; $i < $quantity; ++$i) {
            UserCredit::query()->create([
                'owner_id' => $owner->getKey(),
                'holder_id' => $owner->getKey(),
                'company_id' => $company->getKey(),
                'status' => UserCreditStatusEnum::Available,
            ]);
        }
    }
}
