<?php

namespace App\DTO;

use App\Enums\AppointmentCategoryEnum;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use JsonSerializable;

class BookAppointmentDTO implements JsonSerializable
{
    public function __construct(
        public int|string $userId,
        public AppointmentCategoryEnum $categoryType,
        public CarbonInterface $appointmentAt,
        public int $voucherId,
        public ?string $notes = null,
    ) {}

    public static function make(int|string $userId, array $payload): self
    {
        return new self(
            userId: $userId,
            categoryType: AppointmentCategoryEnum::from($payload['category_type']),
            appointmentAt: Carbon::parse($payload['appointment_at']),
            voucherId: $payload['voucher_id'],
            notes: $payload['notes'] ?? null,
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'userId' => $this->userId,
            'category_type' => $this->categoryType->value,
            'appointment_at' => $this->appointmentAt->getTimestampMs(),
            'voucher_id' => $this->voucherId,
            'notes' => $this->notes,
        ];
    }
}
