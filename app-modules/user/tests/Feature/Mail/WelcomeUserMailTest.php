<?php

use App\Filament\FilamentPanel;
use App\Models\Users\User;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use TresPontosTech\Permissions\Roles;
use TresPontosTech\User\Mail\WelcomeUserMail;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

describe('WelcomeUserMail', function (): void {
    it('has correct subject', function (): void {
        $user = User::factory()->create();

        $mailable = new WelcomeUserMail($user);

        $mailable->assertHasSubject(__('user::mail.welcome.subject', ['app_name' => config('app.name')]));
    });

    it('renders user name and app name in HTML', function (): void {
        $user = User::factory()->create(['name' => 'Paula Araujo']);

        $mailable = new WelcomeUserMail($user);

        $mailable->assertSeeInHtml('Paula Araujo');
        $mailable->assertSeeInHtml(config('app.name'));
    });

    it('renders platform access button', function (): void {
        $user = User::factory()->create();

        $mailable = new WelcomeUserMail($user);

        $mailable->assertSeeInHtml('Acessar plataforma');
    });

    it('does not show password section when no temporary password is given', function (): void {
        $user = User::factory()->create();

        $mailable = new WelcomeUserMail($user);

        $mailable->assertDontSeeInHtml('Senha temporária');
    });

    it('renders temporary password when provided', function (): void {
        $user = User::factory()->create(['email' => 'joao@empresa.com']);

        $mailable = new WelcomeUserMail($user, 'Abc123!@#xyz');

        $mailable->assertSeeInHtml('Abc123!@#xyz');
        $mailable->assertSeeInHtml('Senha temporária');
        $mailable->assertSeeInHtml('joao@empresa.com');
    });

    it('renders the set-password button instead of credentials when the user must set one', function (): void {
        $user = User::factory()->create(['email' => 'joao@empresa.com']);

        $mailable = new WelcomeUserMail($user, mustSetPassword: true);

        $mailable->assertSeeInHtml('Definir minha senha');
        $mailable->assertSeeInHtml(route('filament.consultant.auth.password-reset.reset'));
        $mailable->assertSeeInHtml(config('auth.passwords.users.expire') . ' minutos');
        $mailable->assertDontSeeInHtml('Senha temporária');
        $mailable->assertDontSeeInHtml('Acessar plataforma');
    });

    it('mints a token on render that resets the password on the consultant panel', function (): void {
        $user = User::factory()->create(['email' => 'joao@empresa.com']);
        $user->assignRole(Roles::Consultant->value);

        $html = (new WelcomeUserMail($user, mustSetPassword: true))->render();
        preg_match('/token=([0-9a-f]+)/', $html, $matches);
        preg_match('#href="([^"]*password-reset/reset[^"]*)"#', $html, $setPasswordLink);

        expect(Password::tokenExists($user, $matches[1]))->toBeTrue();

        get(html_entity_decode($setPasswordLink[1]))->assertOk();

        filament()->setCurrentPanel(FilamentPanel::Consultant->value);

        livewire(ResetPassword::class, ['email' => 'joao@empresa.com', 'token' => $matches[1]])
            ->fillForm(['password' => 'nova-senha-123', 'passwordConfirmation' => 'nova-senha-123'])
            ->call('resetPassword')
            ->assertHasNoFormErrors();

        expect(Hash::check('nova-senha-123', $user->fresh()->password))->toBeTrue();
    });

    it('is queued to the user email', function (): void {
        Mail::fake();

        $user = User::factory()->create();

        Mail::to($user->email)->queue(new WelcomeUserMail($user));

        Mail::assertQueued(
            WelcomeUserMail::class,
            fn (WelcomeUserMail $mail) => $mail->hasTo($user->email),
        );
    });
});
