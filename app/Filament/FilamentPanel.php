<?php

namespace App\Filament;

use App\Models\Users\User;
use Filament\Panel;
use TresPontosTech\Permissions\Roles;

enum FilamentPanel: string
{
    case User = 'app';

    case Admin = 'admin';

    case Company = 'company';

    case Consultant = 'consultant';

    case Guest = 'guest';

    public static function canAccessPanel(Panel $panel, User $user): bool
    {
        $panel = self::from($panel->getId());
        $isAdmin = $user->hasAnyRole([Roles::SuperAdmin->value, Roles::Admin->value]);
        $isCompanyOwner = $user->hasAnyRole([Roles::CompanyOwner->value]);
        $isEmployee = $user->hasAnyRole([Roles::Employee->value]);

        return match ($panel) {
            self::User => $isEmployee || $isAdmin,
            self::Admin => ($user->hasVerifiedEmail() && $isAdmin),
            self::Company => $isAdmin || $isCompanyOwner,
            self::Consultant => true,
            self::Guest => true,
        };
    }
}
