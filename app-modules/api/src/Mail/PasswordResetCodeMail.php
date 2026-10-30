<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use SensitiveParameter;

/**
 * Só o código, sem link para redefinir: o rastreamento de cliques do provedor e o Safe Links do
 * Outlook abrem links antes da pessoa, e o código é digitado no app. A fila guarda o e-mail
 * criptografado, para o código não ficar legível na tabela `jobs` nem na `failed_jobs`.
 */
final class PasswordResetCodeMail extends Mailable implements ShouldBeEncrypted, ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        #[SensitiveParameter] public readonly string $code,
    ) {
        $this->onQueue('emails')->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('api::mail.password_reset_code.subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.auth.password-reset-code',
            with: [
                'minutes' => (int) config('api.password_reset.code_ttl_minutes'),
            ],
        );
    }
}
