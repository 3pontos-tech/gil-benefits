<?php

declare(strict_types=1);

namespace App\Http\Middleware\Concerns;

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

trait RedirectsToProfileHome
{
    /**
     * Build a redirect to the authenticated user's own profile home, used when
     * they were denied access to a page/company outside their profile. When the
     * user's profile has no mapped home the original denial is preserved (403)
     * and logged so the engineering team can investigate an unexpected, unmapped
     * profile.
     */
    protected function profileHomeResponse(Request $request, User $user): Response
    {
        $homeUrl = FilamentPanel::homeUrlFor($user);

        if ($homeUrl === null) {
            Log::warning('Authenticated user without a mapped profile home was denied access.', [
                'user_id' => $user->getKey(),
                'roles' => $user->getRoleNames()->all(),
                'path' => $request->path(),
            ]);

            abort(Response::HTTP_FORBIDDEN);
        }

        // Defensive loop guard: never redirect a request onto itself.
        if (rtrim($request->url(), '/') === rtrim((string) strtok($homeUrl, '?'), '/')) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return redirect()->to($homeUrl);
    }
}
