<?php

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use Ramsey\Uuid\Uuid;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentStatus;

readonly class BookAppointmentAction
{
    public function handle(
        BookAppointmentDTO $payload
    ): void {
        $user = User::query()->find($payload->userId);

        $user->appointments()->create([
            ...$payload->jsonSerialize(),
            'status' => AppointmentStatus::Pending,
            'external_opportunity_id' => Uuid::uuid4()->toString(),
            'external_appointment_id' => Uuid::uuid4()->toString(),
        ]);
    }
}
