<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountValueTypeEnum: string implements HasLabel
{
    case Percent = 'percent';
    case FixedAmount = 'fixed_amount';

    public function getLabel(): string
    {
        return match ($this) {
            self::Percent => 'Percentual',
            self::FixedAmount => 'Valor fixo',
        };
    }
}
