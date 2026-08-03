<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\DTO;

use JsonSerializable;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;

final readonly class StoreAppointmentHistoryDTO implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function __construct(
        public string $appointmentId,
        public string $actorId,
        public AppointmentHistoryActor $actorType,
        public AppointmentHistoryActionType $actionType,
        public array $oldValues,
        public array $newValues,
    ) {}

    /**
     * @param  array{appointment_id: string, actor_id: string, actor_type: string, action_type: string, old_values: array<string, mixed>, new_values: array<string, mixed>}  $data
     */
    public static function make(array $data): self
    {
        return new self(
            appointmentId: $data['appointment_id'],
            actorId: $data['actor_id'],
            actorType: AppointmentHistoryActor::from($data['actor_type']),
            actionType: AppointmentHistoryActionType::from($data['action_type']),
            oldValues: $data['old_values'],
            newValues: $data['new_values'],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'appointment_id' => $this->appointmentId,
            'actor_id' => $this->actorId,
            'actor_type' => $this->actorType,
            'action_type' => $this->actionType,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
        ];
    }
}
