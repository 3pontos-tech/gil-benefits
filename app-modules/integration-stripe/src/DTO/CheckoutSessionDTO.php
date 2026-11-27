<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\DTO;

/**
 * A Checkout Session in our terms. `toArray()` is the only place that knows the Stripe
 * request shape, so an API change is fixed here and nowhere else.
 */
final readonly class CheckoutSessionDTO
{
    public const string REFERENCE_KEY = 'checkout_reference';

    private function __construct(
        public string $mode,
        public string $customerId,
        public CheckoutLineItemDTO $lineItem,
        public string $successUrl,
        public string $cancelUrl,
        public bool $acceptsPix,
        public ?int $pixMandateCents = null,
        public ?string $couponId = null,
        public ?string $reference = null,
    ) {}

    /**
     * `pixMandateCents` caps every Pix Automático charge of the subscription; null offers
     * card only. Stripe refuses `amount_type` in subscription mode and treats the amount
     * as the maximum per cycle.
     */
    public static function subscription(
        string $customerId,
        string $reference,
        CheckoutLineItemDTO $lineItem,
        string $successUrl,
        string $cancelUrl,
        ?int $pixMandateCents,
        ?string $couponId,
    ): self {
        return new self(
            mode: 'subscription',
            customerId: $customerId,
            lineItem: $lineItem,
            successUrl: $successUrl,
            cancelUrl: $cancelUrl,
            acceptsPix: $pixMandateCents !== null,
            pixMandateCents: $pixMandateCents,
            couponId: $couponId,
            reference: $reference,
        );
    }

    public static function payment(
        string $customerId,
        CheckoutLineItemDTO $lineItem,
        string $successUrl,
        string $cancelUrl,
        bool $acceptsPix,
    ): self {
        return new self(
            mode: 'payment',
            customerId: $customerId,
            lineItem: $lineItem,
            successUrl: $successUrl,
            cancelUrl: $cancelUrl,
            acceptsPix: $acceptsPix,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $session = [
            'mode' => $this->mode,
            'customer' => $this->customerId,
            'success_url' => $this->successUrl,
            'cancel_url' => $this->cancelUrl,
            'line_items' => [$this->lineItem->toArray()],
            'allowed_payment_method_types' => $this->acceptsPix ? ['card', 'pix'] : ['card'],
        ];

        if ($this->reference !== null) {
            $session['client_reference_id'] = $this->reference;
            $session['metadata'] = [self::REFERENCE_KEY => $this->reference];
            $session['subscription_data'] = ['metadata' => [self::REFERENCE_KEY => $this->reference]];
        }

        if ($this->pixMandateCents !== null) {
            $session['payment_method_options'] = [
                'pix' => [
                    'mandate_options' => [
                        'amount' => $this->pixMandateCents,
                        'payment_schedule' => 'monthly',
                    ],
                ],
            ];
        }

        if ($this->couponId !== null) {
            $session['discounts'] = [['coupon' => $this->couponId]];
        }

        return $session;
    }
}
