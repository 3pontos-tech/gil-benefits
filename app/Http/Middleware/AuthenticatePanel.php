<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Middleware\Concerns\RedirectsToProfileHome;
use App\Models\Users\User;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Drop-in replacement for Filament's Authenticate middleware.
 *
 * It keeps the exact authentication behavior (unauthenticated users are still
 * sent to the login page) but, instead of aborting with a 403 when an
 * authenticated user cannot access the current panel, it redirects them to the
 * home of their own profile. Only `authenticate()` is overridden so that the
 * parent `handle()` — and therefore Livewire's persistent middleware — keeps
 * behaving exactly like Filament's original. The redirect is delivered through
 * an HttpResponseException, which Laravel turns into the response directly.
 */
class AuthenticatePanel extends Authenticate
{
    use RedirectsToProfileHome;

    /**
     * @param  array<string>  $guards
     */
    protected function authenticate($request, array $guards): void
    {
        $guard = Filament::auth();

        // Unauthenticated users keep the current behavior: sent to the login page.
        if (! $guard->check()) {
            $this->unauthenticated($request, $guards);

            return; /** @phpstan-ignore-line */
        }

        $this->auth->shouldUse(Filament::getAuthGuard());

        /** @var Model $user */
        $user = $guard->user();

        $panel = Filament::getCurrentOrDefaultPanel();

        if ($user instanceof FilamentUser && ! $user->canAccessPanel($panel)) {
            // Only our own user model can resolve a profile home; anything else
            // keeps Filament's original 403.
            if ($user instanceof User) {
                throw new HttpResponseException($this->profileHomeResponse($request, $user));
            }

            abort(Response::HTTP_FORBIDDEN);
        }

        // Preserve Filament's production safeguard for non-FilamentUser models.
        abort_if(
            ! $user instanceof FilamentUser && config('app.env') !== 'local',
            Response::HTTP_FORBIDDEN,
        );
    }
}
