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
        $isConsultant = $user->hasAnyRole([Roles::Consultant->value]);

        // Company roles live in the pivot, so panel access is relationship-based.
        $belongsToCompany = $user->ownsAnyCompany() || $user->companies()->exists();

        return match ($panel) {
            self::User => $belongsToCompany || $isAdmin,
            self::Admin => ($user->hasVerifiedEmail() && $isAdmin),
            self::Company => Gate::forUser($user)->allows('register_company') || $user->managesAnyCompany(),
            self::Consultant => $isConsultant || $isAdmin,
            self::Guest => true,
        };
    }
}
