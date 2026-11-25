<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountCustomerTypeEnum: string implements HasLabel
{
    case Person = 'person';
    case Company = 'company';

    public function getLabel(): string
    {
        return match ($this) {
            self::Person => 'Pessoa física',
            self::Company => 'Empresa',
        };
    }
}
