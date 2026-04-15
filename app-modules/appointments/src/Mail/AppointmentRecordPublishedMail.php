<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use TresPontosTech\App\Filament\Resources\Appointments\AppointmentResource;
use TresPontosTech\Appointments\Models\AppointmentRecord;

final class AppointmentRecordPublishedMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public AppointmentRecord $record,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Sua ata está disponível',
        );
    }

    public function content(): Content
    {
        $this->record->loadMissing(['appointment.company', 'appointment.consultant', 'appointment.user']);

        return new Content(
            markdown: 'appointments::mail.record-published',
            with: [
                'record' => $this->record,
                'url' => AppointmentResource::getUrl(
                    panel: 'app',
                    tenant: $this->record->appointment->company,
                ),
            ],
        );
    }
}
