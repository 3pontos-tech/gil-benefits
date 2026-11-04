<?php

declare(strict_types=1);

namespace TresPontosTech\User\Mail;

use App\Models\Users\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * O botão leva à página de recuperação do painel do colaborador, e não a um link de uso único:
 * um scanner de e-mail que o abra não estraga nada.
 */
final class PasswordChangedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly CarbonInterface $changedAt,
    ) {
        $this->onQueue('emails')->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('user::mail.password_changed.subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        $changedAt = $this->changedAt->copy()->setTimezone((string) config('app.timezone'));

        return new Content(
            markdown: 'emails.users.password-changed',
            with: [
                'userName' => $this->user->name,
                'date' => $changedAt->format('d/m/Y'),
                'time' => $changedAt->format('H:i'),
                'resetUrl' => route('filament.app.auth.password-reset.request'),
            ],
        );
    }
}
