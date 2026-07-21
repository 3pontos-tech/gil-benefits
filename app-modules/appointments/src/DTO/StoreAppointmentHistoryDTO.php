<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\DTO;

use JsonSerializable;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActionType;

final readonly class StoreAppointmentHistoryDTO implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function __construct(
        public string $appointmentId,
        public string $adminId,
        public AppointmentHistoryActionType $actionType,
        public array $oldValues,
        public array $newValues,
    ) {}

    /**
     * @param  array{appointment_id: string, admin_id: string, action_type: string, old_values: array<string, mixed>, new_values: array<string, mixed>}  $data
     */
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

    /**
     * @return array<string, mixed>
     */
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
