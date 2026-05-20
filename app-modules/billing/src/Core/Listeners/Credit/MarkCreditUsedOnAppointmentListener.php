<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Listeners\Credit;

use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Events\Credit\AppointmentCreditUsed;
use TresPontosTech\Billing\Core\Models\UserCredit;

class MarkCreditUsedOnAppointmentListener
{
    public function handle(AppointmentCreditUsed $event): void
    {
        UserCredit::query()
            ->where('appointment_id', $event->appointmentId)
            ->where('status', UserCreditStatusEnum::InUse)
            ->update(['status' => UserCreditStatusEnum::Used]);
    }
}
