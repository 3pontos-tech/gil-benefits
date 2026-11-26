<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DiscountRedemptionStatusEnum: string implements HasColor, HasLabel
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

    public function getColor(): string
    {
        return match ($this) {
            self::Reserved => 'info',
            self::Active => 'success',
            self::Completed => 'gray',
            self::Released => 'warning',
            self::Canceled => 'danger',
        };
    }
}
