<?php

namespace TresPontosTech\Appointments\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use TresPontosTech\Appointments\Models\Appointment;

class AppointmentRequestedAdminMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
    ) {
        $this->onQueue('emails')->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('appointments::mail.requested_admin.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointments.requested-admin',
            with: [
                'userName' => $this->appointment->user->name,
                'categoryLabel' => $this->appointment->category_type->getLabel(),
                'appointmentAt' => $this->appointment->appointment_at,
                'notes' => $this->appointment->notes,
                'panelUrl' => $this->resolvePanelUrl(),
            ],
        );
    }

    private function resolvePanelUrl(): string
    {
        return route('filament.admin.resources.appointments.view', $this->appointment);
    }
}
