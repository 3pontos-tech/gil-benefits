<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte\DTOs;

readonly class CreatePaymentLinkDto
{
    /**
     * @param  list<array<string, mixed>>  $metadata
     * @param  list<string>  $paymentMethods
     */
    public function __construct(
        public string $uuidSellerClient,
        public string $scheduledDate,
        public array $metadata = [],
        public string $type = 'SUBSCRIPTION',
        public array $paymentMethods = ['PIX', 'CREDIT_CARD'],
        public ?PaymentSubscriptionDto $paymentSubscription = null,
        public ?PaymentOrderDto $paymentOrder = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'type' => $this->type,
            'uuidSellerClient' => $this->uuidSellerClient,
            'scheduledDate' => $this->scheduledDate,
            'paymentMethods' => $this->paymentMethods,
            'metadata' => $this->metadata,
        ];

        if ($this->paymentSubscription instanceof PaymentSubscriptionDto) {
            $data['paymentSubscription'] = $this->paymentSubscription->toArray();
        }

        if ($this->paymentOrder instanceof PaymentOrderDto) {
            $data['paymentOrder'] = $this->paymentOrder->toArray();
        }

        return $data;
    }
}
