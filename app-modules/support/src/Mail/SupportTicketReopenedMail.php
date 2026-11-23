<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use TresPontosTech\Support\Models\SupportTicket;

class SupportTicketReopenedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly SupportTicket $ticket,
        public readonly ?string $reason,
        public readonly ?string $reopenedBy,
    ) {
        $this->onQueue('emails')->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('support::mail.reopened.subject', [
                'protocol' => $this->ticket->protocol,
                'subject' => $this->ticket->subject,
            ]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'support::emails.reopened',
            with: [
                'ticket' => $this->ticket,
                'reason' => $this->reason,
                'reopenedBy' => $this->reopenedBy,
            ],
        );
    }
}
