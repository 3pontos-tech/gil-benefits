<?php

namespace TresPontosTech\Appointments\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use TresPontosTech\Appointments\Models\Appointment;

class AppointmentScheduledMail extends Mailable implements ShouldQueue
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
            subject: __('appointments::mail.scheduled.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointments.scheduled',
            with: [
                'consultantName' => $this->appointment->consultant->name,
                'userName' => $this->appointment->user->name,
                'appointmentAt' => $this->appointment->appointment_at,
                'meetingUrl' => $this->appointment->meeting_url,
                'notes' => $this->appointment->notes,
                'panelUrl' => $this->resolvePanelUrl(),
            ],
        );
    }

    private function resolvePanelUrl(): string
    {
        return route('filament.consultant.resources.appointments.index');
    }
}
