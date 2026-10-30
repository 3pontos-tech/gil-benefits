<?php

declare(strict_types=1);

use App\Filament\FilamentPanel;
use App\Filament\Shared\Pages\RequestPasswordResetPage;
use App\Models\Users\User;
use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use TresPontosTech\Api\Models\PasswordResetCode;
use TresPontosTech\User\Mail\PasswordChangedMail;

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

it('clears the app code, ends the web sessions and mails the notice on a reset through the website', function (): void {
    Mail::fake();
    config()->set('session.driver', 'database');
    $user = standaloneSubscriber();
    PasswordResetCode::factory()->withCode()->for($user)->create(['failed_attempts' => 7]);
    DB::table('sessions')->insert([
        'id' => 'panel-session',
        'user_id' => $user->getKey(),
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => '',
        'last_activity' => now()->getTimestamp(),
    ]);

    livewire(ResetPassword::class, ['email' => $user->email, 'token' => Password::broker()->createToken($user)])
        ->fillForm(['password' => 'NovaSenha#2026', 'passwordConfirmation' => 'NovaSenha#2026'])
        ->call('resetPassword')
        ->assertHasNoFormErrors();

    expect(PasswordResetCode::query()->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse();

    Mail::assertQueued(PasswordChangedMail::class, fn (PasswordChangedMail $mail): bool => $mail->hasTo($user->email));
});

it('tells the owner when and how to react in the password-changed mail', function (): void {
    config()->set('app.name', 'Flamma');
    $user = User::factory()->create(['name' => 'Maria']);
    $mail = new PasswordChangedMail($user, now()->setTimezone('UTC')->setDate(2026, 10, 29)->setTime(18, 5));

    $mail->assertHasSubject('Sua senha do Flamma foi alterada');
    $mail->assertSeeInHtml('Olá, Maria.')
        ->assertSeeInHtml('A senha da sua conta foi alterada em 29/10/2026 às 15:05.')
        ->assertSeeInHtml('Todos os aparelhos e sessões foram desconectados.')
        ->assertSeeInHtml(route('filament.app.auth.password-reset.request'));
});
