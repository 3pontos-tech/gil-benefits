<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\DTO;

use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;

/**
 * A single-use coupon mirroring a discount resolved by the `discounts` module.
 */
final readonly class CouponDTO
{
    private function __construct(
        public ?int $percentOff,
        public ?int $amountOffCents,
        public string $currency,
        public ?int $durationInMonths,
    ) {}

    public static function fromDiscount(CheckoutDiscount $discount, string $currency): self
    {
        return new self(
            percentOff: $discount->percentOff,
            amountOffCents: $discount->amountOffCents,
            currency: $currency,
            durationInMonths: $discount->durationCycles,
        );
    }

    /**
     * @return array{percent_off?: int, amount_off?: int, currency?: string, duration: string, duration_in_months?: int, max_redemptions: int}
     */
    public function toArray(): array
    {
        $coupon = $this->amountOffCents === null
            ? ['percent_off' => (int) $this->percentOff]
            : ['amount_off' => $this->amountOffCents, 'currency' => $this->currency];

        if ($this->durationInMonths === null) {
            return [...$coupon, 'duration' => 'forever', 'max_redemptions' => 1];
        }

        return [...$coupon, 'duration' => 'repeating', 'duration_in_months' => $this->durationInMonths, 'max_redemptions' => 1];
    }
}
