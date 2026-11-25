<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\DTOs;

use Illuminate\Support\Carbon;
use Illuminate\Support\Number;
use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Models\Discount;
use TresPontosTech\Discounts\Models\DiscountGrant;

final readonly class DiscountOffer
{
    public function __construct(
        public string $key,
        public string $label,
        public CheckoutDiscount $checkoutDiscount,
        public int $listAmountCents,
        public int $discountedAmountCents,
        public int $savingPerCycleCents,
        public int $score,
        public ?Carbon $expiresAt,
        public Discount $discount,
        public ?DiscountGrant $grant,
    ) {}

    public function summary(): string
    {
        $value = $this->checkoutDiscount->percentOff !== null
            ? $this->checkoutDiscount->percentOff . '% de desconto'
            : Number::currency($this->checkoutDiscount->amountOffCents / 100, 'BRL') . ' de desconto';

        return $value . ' ' . $this->durationLabel();
    }

    private function durationLabel(): string
    {
        return match ($this->discount->duration) {
            DiscountDurationEnum::Forever => 'em todas as mensalidades',
            DiscountDurationEnum::Once => 'só na primeira cobrança',
            DiscountDurationEnum::Repeating => ($this->discount->duration_cycles ?? 1) === 1
                ? 'na primeira cobrança'
                : 'nas ' . $this->discount->duration_cycles . ' primeiras cobranças',
        };
    }
}
