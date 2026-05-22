<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions\Credit;

use TresPontosTech\Billing\Core\DTOs\CreditDTO;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;

class PurchaseCredits
{
    public function handle(CreditDTO $dto): void
    {
        for ($i = 0; $i < $dto->quantity; ++$i) {
            UserCredit::query()->create([
                'owner_id' => $dto->ownerId,
                'holder_id' => $dto->holderId,
                'company_id' => $dto->companyId,
                'status' => UserCreditStatusEnum::Available,
            ]);
        }
    }
}
