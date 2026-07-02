<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions\Credit;

use TresPontosTech\Billing\Core\DTOs\CreditDTO;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;

/**
 * Mechanic for bringing credits into existence: creates N available
 * {@see UserCredit} rows. Reason-agnostic — purchases and admin grants both
 * funnel through here, each passing the fields relevant to its origin.
 */
final readonly class IssueCredits
{
    public function handle(CreditDTO $dto): void
    {
        for ($i = 0; $i < $dto->quantity; ++$i) {
            UserCredit::query()->create([
                'owner_id' => $dto->ownerId,
                'holder_id' => $dto->holderId,
                'company_id' => $dto->companyId,
                'grant_id' => $dto->grantId,
                'status' => UserCreditStatusEnum::Available,
            ]);
        }
    }
}
