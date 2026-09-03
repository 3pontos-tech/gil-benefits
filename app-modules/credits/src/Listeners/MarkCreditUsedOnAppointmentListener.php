<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Listeners;

use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Events\AppointmentCreditUsed;
use TresPontosTech\Credits\Models\UserCredit;

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
