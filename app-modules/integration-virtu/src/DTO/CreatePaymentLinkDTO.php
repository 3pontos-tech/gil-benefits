<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\DTO;

use TresPontosTech\IntegrationVirtu\Enums\VirtuIntervalEnum;

/**
 * Body of POST /api/v1/public/payment-links.
 *
 * There is no plan reference: the amount lives on the link itself, so the whole
 * price catalogue stays on our side. Build instances through subscription() or
 * order() — they encode constraints the API enforces but does not default.
 */
final readonly class CreatePaymentLinkDTO
{
    /**
     * @param  list<string>  $acceptedMethods
     */
    private function __construct(
        public string $title,
        public int $amountCents,
        public array $acceptedMethods,
        public string $interestMode,
        public int $maxInstallments,
        public ?string $kind = null,
        public ?VirtuIntervalEnum $interval = null,
        public ?int $trialDays = null,
        public ?string $description = null,
    ) {}

    /**
     * Recurring link. Credit card only — the API rejects PIX and BOLETO for
     * kind=SUBSCRIPTION — and installments are capped at 12 regardless of what
     * config asks for. The buyer picks the installment count at checkout; it is
     * not fixed at creation.
     */
    public static function subscription(
        string $title,
        int $amountCents,
        VirtuIntervalEnum $interval = VirtuIntervalEnum::Monthly,
        ?int $trialDays = null,
    ): self {
        return new self(
            title: $title,
            amountCents: $amountCents,
            acceptedMethods: array_values((array) config('virtu.subscription_methods')),
            interestMode: (string) config('virtu.interest_mode'),
            maxInstallments: min((int) config('virtu.max_installments'), 12),
            kind: 'SUBSCRIPTION',
            interval: $interval,
            trialDays: $trialDays,
        );
    }

    /**
     * One-off link. Omits `kind`, which is what makes it a single payment, and
     * may accept PIX. Used for credit purchases.
     */
    public static function order(
        string $title,
        int $amountCents,
        int $maxInstallments = 1,
        ?string $description = null,
    ): self {
        return new self(
            title: $title,
            amountCents: $amountCents,
            acceptedMethods: array_values((array) config('virtu.order_methods')),
            interestMode: (string) config('virtu.interest_mode'),
            maxInstallments: $maxInstallments,
            description: $description,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'checkoutType' => 'GENERIC',
            'title' => $this->title,
            'description' => $this->description,
            'amountCents' => $this->amountCents,
            'acceptedMethods' => $this->acceptedMethods,
            'interestMode' => $this->interestMode,
            'maxInstallments' => $this->maxInstallments,
            'kind' => $this->kind,
            'interval' => $this->interval?->value,
            'trialDays' => $this->trialDays,
        ], fn (mixed $value): bool => $value !== null);
    }
}
