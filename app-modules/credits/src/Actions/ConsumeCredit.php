<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Actions;

use TresPontosTech\Credits\DTOs\CreditDTO;
use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

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
