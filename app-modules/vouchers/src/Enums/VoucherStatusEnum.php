<?php

namespace TresPontosTech\Vouchers\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum VoucherStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';

    case Pending = 'pending';

    case Used = 'used';
    case Requested = 'requested';

    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Requested => 'Solicitado',
            self::Pending => 'Pendente',
            self::Active => 'Ativo',
            self::Used => 'Utilizado',
            self::Expired => 'Expirado',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Requested => Heroicon::OutlinedDocumentText,
            self::Pending => Heroicon::OutlinedClock,
            self::Active => Heroicon::OutlinedCheckCircle,
            self::Used => Heroicon::OutlinedDocumentCheck,
            self::Expired => Heroicon::OutlinedXCircle,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Requested => 'gray',
            self::Pending => 'warning',
            self::Active => 'success',
            self::Used => 'info',
            self::Expired => 'danger',
        };
    }
}
