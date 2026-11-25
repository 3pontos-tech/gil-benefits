<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountDistributionEnum: string implements HasLabel
{
    case Grant = 'grant';
    case Code = 'code';
    case Automatic = 'automatic';

    public function getLabel(): string
    {
        return match ($this) {
            self::Grant => 'Concedido a uma pessoa',
            self::Code => 'Código digitado',
            self::Automatic => 'Automático',
        };
    }
}
