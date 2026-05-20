<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Actions\Credit;

use TresPontosTech\Billing\Core\DTOs\CreditDTO;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;

final readonly class ConsumeCredit
{
    public function execute(CreditDTO $dto): void
    {
        UserCredit::query()
            ->where('holder_id', $dto->holderId)
            ->where('status', UserCreditStatusEnum::Available)
            ->oldest()
            ->first()
            ?->update([
                'status' => UserCreditStatusEnum::InUse,
                'appointment_id' => $dto->appointmentId,
            ]);
    }
}
