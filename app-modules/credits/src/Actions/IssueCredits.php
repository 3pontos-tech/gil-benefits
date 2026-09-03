<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Actions;

use Illuminate\Support\Facades\DB;
use TresPontosTech\Credits\DTOs\CreditDTO;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

/**
 * Mechanic for bringing credits into existence: creates N available
 * {@see UserCredit} rows atomically. Reason-agnostic — purchases and admin grants
 * both funnel through here, each passing the fields relevant to its origin.
 */
final readonly class IssueCredits
{
    public function handle(CreditDTO $dto): void
    {
        DB::transaction(function () use ($dto): void {
            for ($i = 0; $i < $dto->quantity; ++$i) {
                UserCredit::query()->create([
                    'owner_id' => $dto->ownerId,
                    'holder_id' => $dto->holderId,
                    'company_id' => $dto->companyId,
                    'grant_id' => $dto->grantId,
                    'credit_order_id' => $dto->creditOrderId,
                    'status' => UserCreditStatusEnum::Available,
                ]);
            }
        });
    }
}
