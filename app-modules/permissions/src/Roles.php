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

    case Consultant = 'consultant';

    case Financial = 'financial';
    case CustomerSuccess = 'customer_success';

    public function getColor(): array
    {
        return match ($this) {
            self::CompanyOwner => Color::Red,
            self::CompanyManager => Color::Blue,
            self::Employee => Color::hex('#8282CD'),
            self::SuperAdmin => Color::Fuchsia,
            self::Admin => Color::Cyan,
            self::User => Color::Indigo,
            self::Consultant => Color::Purple,
            self::Financial => Color::Emerald,
            self::CustomerSuccess => Color::Teal,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::CompanyOwner => 'Dono da Empresa',
            self::CompanyManager => 'Gerente',
            self::Employee => 'Funcionário',
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::User => 'Usuario',
            self::Consultant => 'Consultor',
            self::Financial => 'Financeiro',
            self::CustomerSuccess => 'Customer Success',
        };
    }

    /**
     * Papéis que enxergam o cockpit financeiro.
     *
     * O financeiro vê tudo; o CS vê apenas as páginas de saúde do cliente, que
     * checam este conjunto, enquanto as de dinheiro checam `Financial` direto.
     *
     * @return list<self>
     */
    public static function financialCases(): array
    {
        return [self::SuperAdmin, self::Financial, self::CustomerSuccess];
    }

    /**
     * @return list<string>
     */
    public static function financialValues(): array
    {
        return array_map(static fn (self $role): string => $role->value, self::financialCases());
    }
}
