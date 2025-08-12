<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum VoucherStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Active = 'active';
    case Used = 'used';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Active => 'Ativo',
            self::Used => 'Utilizado',
            self::Expired => 'Expirado',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Active => Heroicon::CheckCircle,
            self::Used => Heroicon::DocumentCheck,
            self::Expired => Heroicon::XCircle,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Active => 'success',
            self::Used => 'info',
            self::Expired => 'danger',
        };
    }
}
