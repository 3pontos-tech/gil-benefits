<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\DTO;

use TresPontosTech\IntegrationVirtu\Enums\VirtuWebhookEventEnum;

/**
 * Parsed Virtu webhook payload.
 *
 * Everything meaningful sits under `data`. Money arrives as a decimal string in
 * reais ("297.00") — the opposite of the REST API, which is cents-only — so no
 * amount is exposed here as an int without an explicit conversion at the point
 * of use.
 */
final readonly class VirtuWebhookDTO
{
    /**
     * @param  array<array-key, mixed>  $subscriptions
     */
    public function __construct(
        public ?VirtuWebhookEventEnum $event,
        public ?string $idempotencyKey,
        public ?string $occurredAt,
        public ?string $saleId,
        public ?string $checkoutId,
        public ?string $status,
        public ?string $paymentStatus,
        public ?string $customerTaxId,
        public ?string $customerEmail,
        public array $subscriptions = [],
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        /** @var array<string, mixed> $data */
        $data = $payload['data'] ?? [];

        /** @var array<string, mixed> $customer */
        $customer = $data['customer'] ?? [];

        /** @var array<array-key, mixed> $subscriptions */
        $subscriptions = $data['subscriptions'] ?? [];

        return new self(
            event: VirtuWebhookEventEnum::tryFrom((string) ($payload['event'] ?? '')),
            idempotencyKey: isset($payload['idempotencyKey']) ? (string) $payload['idempotencyKey'] : null,
            occurredAt: isset($payload['occurredAt']) ? (string) $payload['occurredAt'] : null,
            saleId: isset($data['saleId']) ? (string) $data['saleId'] : null,
            checkoutId: isset($data['checkoutId']) ? (string) $data['checkoutId'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
            paymentStatus: isset($data['paymentStatus']) ? (string) $data['paymentStatus'] : null,
            customerTaxId: isset($customer['cpf']) ? (string) $customer['cpf'] : null,
            customerEmail: isset($customer['email']) ? (string) $customer['email'] : null,
            subscriptions: $subscriptions,
        );
    }

    public function isPaid(): bool
    {
        return $this->paymentStatus === 'PAID' || $this->status === 'SUCCESS';
    }

    public function isSubscriptionCharge(): bool
    {
        return $this->subscriptions !== [];
    }
}
