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
        public int|string|null $companyId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  int|string|null  $companyId  Company whose benefit programme this session
     *                                      belongs to — the tenant the employee is booking
     *                                      under. Omit to derive it from their membership.
     */
    public static function make(int|string $userId, array $payload, int|string|null $companyId = null): self
    {
        return new self(
            userId: $userId,
            categoryType: AppointmentCategoryEnum::from($payload['category_type']),
            appointmentAt: Date::parse($payload['appointment_at']),
            notes: $payload['notes'] ?? null,
            companyId: $companyId,
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'userId' => $this->userId,
            'category_type' => $this->categoryType->value,
            'appointment_at' => $this->appointmentAt->toDateTimeString(),
            'notes' => $this->notes,
        ];
    }
}
