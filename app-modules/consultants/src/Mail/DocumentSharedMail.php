<?php

namespace TresPontosTech\Consultants\Mail;

use App\Models\Users\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use TresPontosTech\Consultants\Models\Document;

class DocumentSharedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Document $document,
        public readonly User $employee,
    ) {
        $this->onQueue('emails')->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('consultants::mail.document_shared.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.documents.shared',
            with: [
                'employeeName' => $this->employee->name,
                'documentTitle' => $this->document->title,
                'consultantName' => $this->document->documentable?->name,
                'panelUrl' => $this->resolvePanelUrl(),
            ],
        );
    }

    private function resolvePanelUrl(): string
    {
        $company = $this->employee->companies()->first();

        if (blank($company)) {
            return url('/');
        }

        return route('filament.app.resources.shared-documents.index', ['tenant' => $company]);
    }
}
