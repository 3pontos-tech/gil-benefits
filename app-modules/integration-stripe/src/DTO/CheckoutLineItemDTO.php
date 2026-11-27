<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\DTO;

/**
 * One line of a Checkout Session: a catalog price, or an amount we define inline.
 */
final readonly class CheckoutLineItemDTO
{
    private function __construct(
        public int $quantity,
        public ?string $priceId = null,
        public ?string $productName = null,
        public ?int $unitAmountCents = null,
        public ?string $currency = null,
        public bool $monthly = false,
    ) {}

    public static function catalog(string $priceId, int $quantity): self
    {
        return new self(quantity: $quantity, priceId: $priceId);
    }

    public static function inline(string $productName, int $unitAmountCents, int $quantity, string $currency, bool $monthly): self
    {
        return new self(
            quantity: $quantity,
            productName: $productName,
            unitAmountCents: $unitAmountCents,
            currency: $currency,
            monthly: $monthly,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        if ($this->priceId !== null) {
            return ['price' => $this->priceId, 'quantity' => $this->quantity];
        }

        return [
            'price_data' => array_filter([
                'currency' => $this->currency,
                'unit_amount' => $this->unitAmountCents,
                'product_data' => ['name' => $this->productName],
                'recurring' => $this->monthly ? ['interval' => 'month'] : null,
            ], fn (mixed $value): bool => $value !== null),
            'quantity' => $this->quantity,
        ];
    }
}
