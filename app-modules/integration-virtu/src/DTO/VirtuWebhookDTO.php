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
    private const array TERMINAL_STATUSES = ['CANCELED', 'REFUNDED', 'CHARGEBACK'];

    private const array TERMINATED_SUBSCRIPTION_STATUSES = ['CANCELED', 'INACTIVE'];

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
        public ?string $source = null,
        public ?string $subscriptionStatus = null,
        public ?string $previousSubscriptionStatus = null,
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

        /** @var array<string, mixed> $firstSubscription */
        $firstSubscription = is_array($subscriptions[0] ?? null) ? $subscriptions[0] : [];

        $subscriptionStatus = $data['saleSubscriptionStatus'] ?? $firstSubscription['status'] ?? null;

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
            source: isset($data['source']) ? (string) $data['source'] : null,
            subscriptionStatus: is_scalar($subscriptionStatus) ? (string) $subscriptionStatus : null,
            previousSubscriptionStatus: isset($data['previousStatus']) ? (string) $data['previousStatus'] : null,
        );
    }

    /**
     * Whether the charge behind this payload went through.
     *
     * Only `paymentStatus` answers that. `status` reports whether the event was
     * processed, so it reads SUCCESS on a PIX that was merely issued (verified in
     * sandbox: status SUCCESS with paymentStatus SENT) — trusting it would settle
     * an order nobody paid for.
     *
     * The terminal check is defence in depth for the reverse case: a cancellation
     * keeps paymentStatus PAID forever, so a payload that reached here without
     * `source` must not read as a fresh approval.
     */
    public function isPaid(): bool
    {
        if (in_array($this->status, self::TERMINAL_STATUSES, strict: true)) {
            return false;
        }

        return $this->paymentStatus === 'PAID';
    }

    public function isSubscriptionCharge(): bool
    {
        return $this->subscriptions !== [];
    }

    /**
     * Virtu has no subscription lifecycle event: a status change is multiplexed
     * into TRANSACTION and only `data.source` tells it apart from a sale.
     */
    public function isSubscriptionStatusChange(): bool
    {
        return $this->source === 'SUBSCRIPTION_STATUS_CHANGED';
    }

    /**
     * CANCELED is emitted when Virtu gives up on the retries; INACTIVE follows it
     * up to 24h later, from the expiry routine. Both mean the same to us, and
     * treating the second as a repeat of the first keeps the block idempotent.
     */
    public function isCancellation(): bool
    {
        return in_array($this->subscriptionStatus, self::TERMINATED_SUBSCRIPTION_STATUSES, strict: true);
    }

    /**
     * A recovered payment is reported only here — Virtu sends no SUBSCRIPTION_CHARGE
     * for the retry that goes through, so this is the sole signal that a defaulting
     * subscription is paying again.
     */
    public function isReactivation(): bool
    {
        return $this->subscriptionStatus === 'ACTIVE';
    }

    /**
     * A renewal that fails produces no declined charge at all — verified in
     * sandbox, where nine paid cycles were followed by this single event and no
     * SUBSCRIPTION_CHARGE for the tenth. The status change is the only signal
     * that a subscription stopped paying.
     */
    public function isDelinquent(): bool
    {
        return $this->subscriptionStatus === 'PENDING';
    }
}
