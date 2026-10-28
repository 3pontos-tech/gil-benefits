<?php

namespace TresPontosTech\User\Mail;

use App\Models\Users\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

class WelcomeUserMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly ?string $password = null,
        public readonly bool $mustSetPassword = false,
    ) {
        $this->onQueue('emails')->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('user::mail.welcome.subject', ['app_name' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.users.welcome',
            with: [
                'userName' => $this->user->name,
                'userEmail' => $this->user->email,
                'password' => $this->password,
                'setPasswordUrl' => $this->mustSetPassword ? $this->setPasswordUrl() : null,
                'setPasswordMinutes' => $this->setPasswordMinutes(),
                'panelUrl' => $this->mustSetPassword ? null : $this->resolvePanelUrl(),
            ],
        );
    }

    /**
     * The token is minted here, when the queued mail is rendered, so its expiry counts from
     * the moment the message leaves and not from when the account was created.
     */
    private function setPasswordUrl(): string
    {
        return URL::signedRoute('filament.consultant.auth.password-reset.reset', [
            'email' => $this->user->getEmailForPasswordReset(),
            'token' => Password::createToken($this->user),
        ]);
    }

    private function setPasswordMinutes(): int
    {
        return (int) config(sprintf('auth.passwords.%s.expire', config('auth.defaults.passwords')));
    }

    private function resolvePanelUrl(): string
    {
        $company = $this->user->companies()->first();

        if (blank($company)) {
            return url('/');
        }

        return route('filament.app.pages.user-dashboard', ['tenant' => $company]);
    }
}
