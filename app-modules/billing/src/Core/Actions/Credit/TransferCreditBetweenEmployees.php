<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions\Credit;

use App\Models\Users\User;
use RuntimeException;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;

class TransferCreditBetweenEmployees
{
    public function handle(UserCredit $credit, User $toEmployee): void
    {
        throw_if(
            $credit->status !== UserCreditStatusEnum::Available,
            RuntimeException::class,
            'Only available credits can be transferred.'
        );

        $credit->update([
            'holder_id' => $toEmployee->getKey(),
            'transferred_at' => now(),
        ]);
    }
}
