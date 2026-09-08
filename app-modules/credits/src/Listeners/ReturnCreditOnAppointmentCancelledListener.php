<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Listeners;

use TresPontosTech\Credits\Enums\UserCreditStatusEnum;
use TresPontosTech\Credits\Events\AppointmentCreditReturned;
use TresPontosTech\Credits\Models\UserCredit;

class ReturnCreditOnAppointmentCancelledListener
{
    public function handle(AppointmentCreditReturned $event): void
    {
        UserCredit::query()
            ->where('appointment_id', $event->appointmentId)
            ->where('status', UserCreditStatusEnum::InUse)
            ->update([
                'status' => UserCreditStatusEnum::Available,
                'appointment_id' => null,
            ]);
    }
}
