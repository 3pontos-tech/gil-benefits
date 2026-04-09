<?php

declare(strict_types=1);

namespace App\Filament;

use App\Models\Users\User;
use Filament\Panel;
use Illuminate\Support\Facades\Gate;
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
        $user->hasAnyRole([Roles::CompanyOwner->value]);
        $isEmployee = $user->hasAnyRole([Roles::Employee->value]);
        $isConsultant = $user->hasAnyRole([Roles::Consultant->value]);

        return match ($panel) {
            self::User => $isEmployee || $isAdmin,
            self::Admin => ($user->hasVerifiedEmail() && $isAdmin),
            self::Company => Gate::allows('register_company', $user),
            self::Consultant => $isConsultant || $isAdmin,
            self::Guest => true,
        };
    }
}
