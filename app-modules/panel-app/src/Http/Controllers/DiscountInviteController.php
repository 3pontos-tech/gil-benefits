<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Http\Controllers;

use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use TresPontosTech\Discounts\Models\DiscountGrant;
use TresPontosTech\Discounts\Support\DiscountInviteUrl;

final class DiscountInviteController
{
    public function show(Request $request, string $grant): RedirectResponse
    {
        if (! $request->hasValidRelativeSignatureWhileIgnoring(DiscountInviteUrl::IGNORED_QUERY)
            || ($model = DiscountGrant::query()->find($grant)) === null) {
            Notification::make()->warning()->title('Este link de convite não é válido ou expirou.')->send();

            return to_route('filament.app.auth.register');
        }

        if ($model->user_id !== null) {
            Notification::make()->info()->title('Seu convite já foi ativado. Entre com seu e-mail e senha.')->send();

            return to_route('filament.app.auth.login');
        }

        if (! $model->isPending()) {
            $request->session()->forget(DiscountInviteUrl::SESSION_KEY);
            Notification::make()->warning()->title('Este convite não vale mais. Você ainda pode criar sua conta sem o desconto.')->send();

            return to_route('filament.app.auth.register');
        }

        $request->session()->put(DiscountInviteUrl::SESSION_KEY, $model->getKey());

        return to_route('filament.app.auth.register');
    }

    public function leave(Request $request): RedirectResponse
    {
        $request->session()->forget(DiscountInviteUrl::SESSION_KEY);
        Notification::make()->info()->title('Tudo bem, seguimos sem o desconto.')->send();

        return to_route('filament.app.auth.register');
    }
}
