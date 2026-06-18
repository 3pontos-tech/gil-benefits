<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use TresPontosTech\Support\Models\SupportTicket;

class SupportTicketMail extends Mailable implements ShouldQueue
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
            subject: '[' . $this->ticket->protocol . '] ' . $this->ticket->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'support::emails.ticket',
            with: [
                'ticket' => $this->ticket,
                'channelName' => $this->ticket->category->getDestinationChannel()->getLabel(),
            ],
        );
    }

    public function attachments(): array
    {
        return $this->ticket->getMedia('attachments')
            ->map(fn ($media) => Attachment::fromStorageDisk(
                $media->disk,
                $media->getPathRelativeToRoot(),
            )->as($media->file_name)->withMime($media->mime_type))
            ->all();
    }
}
