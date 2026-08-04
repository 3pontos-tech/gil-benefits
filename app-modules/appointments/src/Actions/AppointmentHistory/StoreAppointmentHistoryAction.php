<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions\AppointmentHistory;

use TresPontosTech\Appointments\DTO\StoreAppointmentHistoryDTO;
use TresPontosTech\Appointments\Models\AppointmentHistory;

final readonly class StoreAppointmentHistoryAction
{
    public function execute(StoreAppointmentHistoryDTO $dto): void
    {
        AppointmentHistory::query()->create([
            'appointment_id' => $dto->appointmentId,
            'actor_id' => $dto->actorId,
            'actor_type' => $dto->actorType,
            'action_type' => $dto->actionType,
            'old_values' => collect($dto->oldValues)->except('updated_at'),
            'new_values' => collect($dto->newValues)->except('updated_at'),
        ]);
    }
}
