<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountDurationEnum: string implements HasLabel
{
    case Forever = 'forever';
    case Once = 'once';
    case Repeating = 'repeating';

    public function getLabel(): string
    {
        return match ($this) {
            self::Forever => 'Todas as mensalidades',
            self::Once => 'Só a primeira cobrança',
            self::Repeating => 'Nas primeiras cobranças',
        };
    }
}
