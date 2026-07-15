<?php

namespace TresPontosTech\Appointments\DTO;

use JsonSerializable;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;

final readonly class StoreAppointmentHistoryDTO implements JsonSerializable
{
    public function __construct(
        public string $appointmentId,
        public string $adminId,
        public AppointmentHistoryActionType $actionType,
        public array $oldValues,
        public array $newValues,
    ) {}

    public static function make(array $data): self
    {
        return new self(
            appointmentId: $data['appointment_id'],
            adminId: $data['admin_id'],
            actionType: AppointmentHistoryActionType::from($data['action_type']),
            oldValues: $data['old_values'],
            newValues: $data['new_values'],
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'appointment_id' => $this->appointmentId,
            'admin_id' => $this->adminId,
            'action_type' => $this->actionType,
            'old_values' => $this->oldValues,
            'new_values' => $this->newValues,
        ];
    }
}
