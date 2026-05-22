<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte\DTOs;

readonly class PaymentOrderDto
{
    public function __construct(
        public string $title,
        public float $value,
        public string $description = '',
        public int $installments = 1,
        public bool $timer = false,
        public ?string $expirationDate = null,
        public array $customInstallmentsValues = [],
        public bool $threeDSecureEnabled = false,
        public bool $requiresLiabilityShift = false,
    ) {}

    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'value' => $this->value,
            'installments' => $this->installments,
            'timer' => $this->timer,
            'expirationDate' => $this->expirationDate,
            'customInstallmentsValues' => $this->customInstallmentsValues,
            'threeDSecureEnabled' => $this->threeDSecureEnabled,
            'requiresLiabilityShift' => $this->requiresLiabilityShift,
        ];
    }
}
