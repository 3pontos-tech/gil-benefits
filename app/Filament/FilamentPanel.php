<?php

declare(strict_types=1);

namespace App\Filament;

use App\Models\Users\User;
use Filament\Facades\Filament;
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

    /**
     * Resolve the panel that represents the given user's own "home", used to
     * redirect an authenticated user who lands on a page (or a company) outside
     * their profile.
     *
     * Panels are checked in priority order and only a panel the user can
     * actually access is returned, which guarantees the redirect target never
     * bounces back into another denial (i.e. no redirect loop). The public
     * Guest panel is intentionally excluded: it is not a profile home, so a
     * user with no accessible profile panel resolves to `null` (see homeUrlFor).
     */
    public static function homePanelFor(User $user): ?self
    {
        foreach ([self::Admin, self::Consultant, self::Company, self::User] as $panel) {
            if (self::canAccessPanel(Filament::getPanel($panel->value), $user)) {
                return $panel;
            }
        }

        return null;
    }

    /**
     * The URL of the given user's profile home, or `null` when the user's
     * profile has no home panel mapped (the caller should keep the denial).
     */
    public static function homeUrlFor(User $user): ?string
    {
        $panel = self::homePanelFor($user);

        if (! $panel instanceof FilamentPanel) {
            return null;
        }

        return Filament::getPanel($panel->value)->getUrl();
    }
}
