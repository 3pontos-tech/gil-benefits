<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\DTO;

use Carbon\CarbonInterface;
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
