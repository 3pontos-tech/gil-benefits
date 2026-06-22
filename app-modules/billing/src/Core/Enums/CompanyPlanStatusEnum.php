<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CompanyPlanStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    public function getColor(): array
    {
        return match ($this) {
            self::Active => Color::Emerald,
            self::Inactive => Color::Gray,
            self::Suspended => Color::Amber,
            self::Cancelled => Color::Red,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Active => 'heroicon-o-check-circle',
            self::Inactive => 'heroicon-o-minus-circle',
            self::Suspended => 'heroicon-o-pause-circle',
            self::Cancelled => 'heroicon-o-x-circle',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => 'Ativo',
            self::Inactive => 'Inativo',
            self::Suspended => 'Suspenso',
            self::Cancelled => 'Cancelado',
        };
    }
}
