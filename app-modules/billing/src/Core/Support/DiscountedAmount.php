<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Support;

final class DiscountedAmount
{
    public static function apply(int $cents, int $percent): int
    {
        return (int) round($cents * (100 - $percent) / 100);
    }
}
