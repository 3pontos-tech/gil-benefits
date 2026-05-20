<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Billing\Core\DTOs\CreditDTO;
use TresPontosTech\Billing\Core\Events\Credit\CreditConsumed;

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
            event(new CreditConsumed(new CreditDTO(
                holderId: $user->getKey(),
                appointmentId: $appointment->getKey(),
            )));
        }
    }
}
