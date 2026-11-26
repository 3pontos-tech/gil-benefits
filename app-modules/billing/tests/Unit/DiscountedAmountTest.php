<?php

declare(strict_types=1);

use TresPontosTech\Billing\Core\Support\DiscountedAmount;

it('aplica o percentual de desconto sobre o valor em centavos', function (int $cents, int $percent, int $expected): void {
    expect(DiscountedAmount::apply($cents, $percent))->toBe($expected);
})->with([
    'R$ 49,90 com 30%' => [4990, 30, 3493],
    'R$ 250,00 com 30%' => [25000, 30, 17500],
    'R$ 350,00 com 30%' => [35000, 30, 24500],
    'sem desconto' => [100, 0, 100],
]);
