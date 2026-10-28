<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Actions;

use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Models\UserCredit;

final readonly class MarkAppointmentCreditUsed
{
    public function execute(string $appointmentId): void
    {
        UserCredit::query()
            ->where('appointment_id', $appointmentId)
            ->inUse()
            ->update(['status' => UserCreditStatusEnum::Used, 'used_at' => now()]);
    }
}
