<?php

declare(strict_types=1);

namespace TresPontosTech\Discounts\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DiscountGrantStatusEnum: string implements HasColor, HasLabel
{
    case Reserved = 'reserved';
    case Claimed = 'claimed';
    case Expired = 'expired';
    case Revoked = 'revoked';

    public function getLabel(): string
    {
        return match ($this) {
            self::Reserved => 'Reservado',
            self::Claimed => 'Ativado',
            self::Expired => 'Vencido',
            self::Revoked => 'Revogado',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Reserved => 'info',
            self::Claimed => 'success',
            self::Expired => 'gray',
            self::Revoked => 'danger',
        };
    }
}
