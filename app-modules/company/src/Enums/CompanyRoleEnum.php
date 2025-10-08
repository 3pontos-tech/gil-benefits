<?php

namespace TresPontosTech\Company\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;

enum CompanyRoleEnum: string implements HasColor
{
    case Owner = 'owner';

    case Manager = 'manager';
    case Employee = 'employee';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Owner => Color::Red,
            self::Manager => Color::Blue,
            self::Employee => Color::hex('#8282CD'),
        };
    }
}
