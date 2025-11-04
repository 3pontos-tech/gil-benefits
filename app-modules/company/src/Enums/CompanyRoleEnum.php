<?php

namespace TresPontosTech\Company\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum CompanyRoleEnum: string implements HasColor, HasLabel
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

    public function getLabel(): string
    {
        return match ($this) {
            self::Owner => 'Administrador',
            self::Manager => 'Gerente',
            self::Employee => 'Funcionário',
        };
    }
}
