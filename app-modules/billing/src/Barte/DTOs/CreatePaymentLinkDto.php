<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte\DTOs;

readonly class CreatePaymentLinkDto
{
    public function __construct(
        public string $uuidSellerClient,
        public PaymentSubscriptionDto $paymentSubscription,
        public string $scheduledDate,
        public array $metadata = [],
        public string $type = 'SUBSCRIPTION',
        public array $paymentMethods = ['PIX', 'CREDIT_CARD'],
    ) {}

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'uuidSellerClient' => $this->uuidSellerClient,
            'scheduledDate' => $this->scheduledDate,
            'paymentMethods' => $this->paymentMethods,
            'paymentSubscription' => $this->paymentSubscription->toArray(),
            'metadata' => $this->metadata,
        ];
    }
}
