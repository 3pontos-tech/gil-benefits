<?php

namespace TresPontosTech\Appointments\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use TresPontosTech\Appointments\Models\Appointment;

class AppointmentUserCancelledLateMail extends Mailable implements ShouldQueue
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
            subject: __('appointments::mail.user_cancelled_late.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.appointments.cancelled-late',
            with: [
                'userName' => $this->appointment->user->name,
                'consultantName' => $this->appointment->consultant?->name ?? __('appointments::mail.no_consultant'),
                'appointmentAt' => $this->appointment->appointment_at,
                'panelUrl' => $this->resolvePanelUrl(),
            ],
        );
    }

    private function resolvePanelUrl(): string
    {
        $this->appointment->loadMissing('company');

        if (blank($this->appointment->company)) {
            return url('/');
        }

        return route('filament.app.resources.appointments.index', ['tenant' => $this->appointment->company]);
    }
}
