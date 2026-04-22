<?php

namespace TresPontosTech\User\Mail;

use App\Models\Users\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly ?string $password = null,
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
                'panelUrl' => $this->resolvePanelUrl(),
            ],
        );
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
