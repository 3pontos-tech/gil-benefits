<?php

namespace TresPontosTech\Appointments\DTO;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;
use JsonSerializable;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;

class BookAppointmentDTO implements JsonSerializable
{
    public function __construct(
        public int|string $userId,
        public AppointmentCategoryEnum $categoryType,
        public CarbonInterface $appointmentAt,
        public ?string $notes = null,
    ) {}

    public static function make(int|string $userId, array $payload): self
    {
        return new self(
            userId: $userId,
            categoryType: AppointmentCategoryEnum::from($payload['category_type']),
            appointmentAt: Date::parse($payload['appointment_at']),
            notes: $payload['notes'] ?? null,
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'userId' => $this->userId,
            'category_type' => $this->categoryType->value,
            'appointment_at' => $this->appointmentAt->getTimestampMs(),
            'notes' => $this->notes,
        ];
    }
}
