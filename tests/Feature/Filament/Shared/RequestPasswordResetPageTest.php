<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Filament\Shared\Pages\RequestPasswordResetPage;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

use function Pest\Livewire\livewire;

/**
 * O "Esqueci minha senha" dos painéis não pode dizer quem tem conta (#323): todo pedido termina
 * como um envio.
 */
beforeEach(function (): void {
    filament()->setCurrentPanel(FilamentPanel::User->value);
    Notification::fake();
});

function resetLinkSentNotification(): FilamentNotification
{
    return FilamentNotification::make()
        ->title(__(Password::RESET_LINK_SENT))
        ->body(__('filament-panels::auth/pages/password-reset/request-password-reset.notifications.sent.body'))
        ->success();
}

it('answers an unknown email like a sent link and clears the form', function (): void {
    livewire(RequestPasswordResetPage::class)
        ->fillForm(['email' => 'ninguem@empresa.com'])
        ->call('request')
        ->assertNotified(resetLinkSentNotification())
        ->assertSchemaStateSet(['email' => null]);

    Notification::assertNothingSent();
});

it('answers a repeated request inside the broker wait like the first one', function (): void {
    $user = standaloneSubscriber();

    livewire(RequestPasswordResetPage::class)
        ->fillForm(['email' => $user->email])
        ->call('request')
        ->assertNotified(resetLinkSentNotification());

    session()->forget('filament.notifications');

    livewire(RequestPasswordResetPage::class)
        ->fillForm(['email' => $user->email])
        ->call('request')
        ->assertNotified(resetLinkSentNotification())
        ->assertSchemaStateSet(['email' => null]);

    Notification::assertSentToTimes($user, ResetPasswordNotification::class, 1);
});
