<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\DTOs;

use InvalidArgumentException;
use TresPontosTech\Billing\Core\Support\DiscountedAmount;

/**
 * Um desconto por compra, já resolvido pelo servidor. Ou o percentual ou o valor fixo,
 * nunca os dois. `durationCycles` null significa todas as recorrências.
 */
final readonly class CheckoutDiscount
{
    public function __construct(
        public ?int $percentOff = null,
        public ?int $amountOffCents = null,
        public ?int $durationCycles = null,
    ) {
        throw_if(
            ($percentOff === null) === ($amountOffCents === null),
            InvalidArgumentException::class,
            'Desconto precisa de exatamente um tipo: percentual ou valor fixo.',
        );
    }

    public function apply(int $cents): int
    {
        if ($this->percentOff !== null) {
            return DiscountedAmount::apply($cents, $this->percentOff);
        }

        return max(0, $cents - $this->amountOffCents);
    }
}
