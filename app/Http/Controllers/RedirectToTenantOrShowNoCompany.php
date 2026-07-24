<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Facades\Filament;
use Filament\Http\Controllers\RedirectToTenantController;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;

/**
 * Drop-in replacement for Filament's RedirectToTenantController (handler for a
 * tenant panel's root, e.g. `/app`).
 *
 * Mirrors the original, except that a NON-admin who reaches the collaborator
 * (app) panel without any active company — typically an employee whose
 * membership is inactive — is shown a friendly "no active company" page instead
 * of a bare 404. Admins keep Filament's pre-existing behavior (they see every
 * company and land on one), so they never reach this branch in practice.
 */
class RedirectToTenantOrShowNoCompany extends RedirectToTenantController
{
    public function __invoke(): RedirectResponse
    {
        $panel = Filament::getCurrentOrDefaultPanel();
        $user = Filament::auth()->user();
        $tenant = Filament::getUserDefaultTenant($user);

        if ($tenant) {
            $url = $panel->getUrl($tenant);

            if (! blank($url)) {
                return redirect()->to($url);
            }
        }

        // Keep Filament's registration flow when the panel offers one.
        if ($panel->hasTenantRegistration() && filament()->getTenantRegistrationPage()::canView()) {
            $registrationUrl = $panel->getTenantRegistrationUrl();

            if ($registrationUrl !== null) {
                return redirect()->to($registrationUrl);
            }
        }

        // A collaborator with no active company: show a message instead of a 404.
        if ($user instanceof User && ! $user->isAdmin() && $panel->getId() === FilamentPanel::User->value) {
            throw new HttpResponseException(response()->view('no-active-company'));
        }

        abort(404);
    }
}
