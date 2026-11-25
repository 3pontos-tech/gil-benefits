<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Enums;

use Filament\Support\Contracts\HasLabel;

enum DiscountRedemptionStatusEnum: string implements HasLabel
{
    case Reserved = 'reserved';
    case Active = 'active';
    case Completed = 'completed';
    case Released = 'released';
    case Canceled = 'canceled';

    public function getLabel(): string
    {
        return match ($this) {
            self::Reserved => 'Reservado',
            self::Active => 'Ativo',
            self::Completed => 'Concluído',
            self::Released => 'Liberado',
            self::Canceled => 'Cancelado',
        };
    }
}
