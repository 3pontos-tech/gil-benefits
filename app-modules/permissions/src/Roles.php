<?php

declare(strict_types=1);

namespace TresPontosTech\Permissions;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Roles: string implements HasColor, HasLabel
{
    case SuperAdmin = 'super_admin';

    case Admin = 'admin';
    case CompanyOwner = 'company_owner';

    case User = 'user';
    case Employee = 'employee';

    case CompanyManager = 'company_manager';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::CompanyOwner => Color::Red,
            self::CompanyManager => Color::Blue,
            self::Employee => Color::hex('#8282CD'),
            self::SuperAdmin => Color::Fuchsia,
            self::Admin => Color::Cyan,
            self::User => Color::Indigo,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::CompanyOwner => 'Administrador',
            self::CompanyManager => 'Gerente',
            self::Employee => 'Funcionário',
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::User => 'Usuario',
        };
    }
}
