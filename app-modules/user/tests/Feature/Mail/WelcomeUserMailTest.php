<?php

use App\Models\Users\User;
use Illuminate\Support\Facades\Mail;
use TresPontosTech\User\Mail\WelcomeUserMail;

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
