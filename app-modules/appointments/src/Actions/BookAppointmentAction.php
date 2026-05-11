<?php

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Billing\Core\Enums\UserCreditStatusEnum;
use TresPontosTech\Billing\Core\Models\UserCredit;

readonly class BookAppointmentAction
{
    public function handle(BookAppointmentDTO $payload): void
    {
        $user = User::query()->find($payload->userId);

        $hasMonthlyQuota = $user->monthly_appointments_left > 0;

        $appointment = $user->appointments()->create([
            ...$payload->jsonSerialize(),
            'status' => AppointmentStatus::Pending,
        ]);

        if (! $hasMonthlyQuota) {
            UserCredit::query()
                ->where('holder_id', $user->getKey())
                ->where('status', UserCreditStatusEnum::Available)
                ->first()
                ?->update([
                    'status' => UserCreditStatusEnum::InUse,
                    'appointment_id' => $appointment->id,
                ]);
        }
    }
}
