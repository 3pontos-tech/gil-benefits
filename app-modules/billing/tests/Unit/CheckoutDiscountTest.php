<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\DTOs\CheckoutDiscount;

it('aplica o percentual sobre o valor em centavos', function (): void {
    expect((new CheckoutDiscount(percentOff: 30))->apply(4990))->toBe(3493);
});

it('abate o valor fixo em centavos', function (): void {
    expect((new CheckoutDiscount(amountOffCents: 5000))->apply(24900))->toBe(19900);
});

it('não deixa o valor fixo passar de zero', function (): void {
    expect((new CheckoutDiscount(amountOffCents: 5000))->apply(3000))->toBe(0);
});

it('recusa desconto sem tipo ou com os dois tipos', function (?int $percentOff, ?int $amountOffCents): void {
    new CheckoutDiscount(percentOff: $percentOff, amountOffCents: $amountOffCents);
})->with([
    'sem tipo' => [null, null],
    'percentual e valor fixo' => [30, 5000],
])->throws(InvalidArgumentException::class);
