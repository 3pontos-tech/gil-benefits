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

class SupportTicketConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly SupportTicket $ticket,
    ) {
        $this->onQueue('emails')->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('support::mail.confirmation.subject', ['protocol' => $this->ticket->protocol]),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'support::emails.confirmation',
            with: [
                'ticket' => $this->ticket,
                'channelName' => $this->ticket->category->getDestinationChannel()->getLabel(),
                'requesterName' => $this->ticket->getRequesterName(),
            ],
        );
    }
}
