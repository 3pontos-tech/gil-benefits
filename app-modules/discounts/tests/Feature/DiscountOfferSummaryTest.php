<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;
use TresPontosTech\Discounts\DTOs\DiscountOffer;
use TresPontosTech\Discounts\Enums\DiscountDurationEnum;
use TresPontosTech\Discounts\Models\Discount;

function discountOfferWith(CheckoutDiscount $checkoutDiscount, Discount $discount): DiscountOffer
{
    return new DiscountOffer(
        key: 'cupom',
        label: 'Cupom',
        checkoutDiscount: $checkoutDiscount,
        listAmountCents: 4990,
        discountedAmountCents: 3990,
        savingPerCycleCents: 1000,
        score: 1,
        expiresAt: null,
        discount: $discount,
        grant: null,
    );
}

it('formata o valor fixo do cupom em reais no padrão brasileiro', function (): void {
    $summary = discountOfferWith(
        new CheckoutDiscount(amountOffCents: 1000),
        Discount::factory()->fixedAmount(1000)->make(['duration' => DiscountDurationEnum::Forever]),
    )->summary();

    expect(str_replace("\u{a0}", ' ', $summary))->toBe('R$ 10,00 de desconto em todas as mensalidades');
});
