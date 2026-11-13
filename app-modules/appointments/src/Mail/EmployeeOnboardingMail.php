<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Mail;

use App\Models\Users\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * O passo a passo até a primeira consultoria, enviado ao colaborador logo depois das boas-vindas
 * (FLM-107). As boas-vindas entregam o acesso; este e-mail conduz o colaborador até o agendamento.
 * Os prazos citados saem das mesmas constantes que o agendamento usa, para o texto não mentir
 * quando a regra mudar.
 */
class EmployeeOnboardingMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  bool  $hasTemporaryPassword  a empresa ou a importação criou a senha, que chegou nas
     *                                      boas-vindas; no auto cadastro a pessoa já escolheu a dela.
     */
    public function __construct(
        public readonly User $user,
        public readonly bool $hasTemporaryPassword,
    ) {
        $this->onQueue('emails')->afterCommit();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('appointments::mail.onboarding.subject', ['app' => config('app.name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.appointments.onboarding',
            with: [
                'userName' => $this->user->name,
                'hasTemporaryPassword' => $this->hasTemporaryPassword,
                'welcomeSubject' => __('user::mail.welcome.subject', ['app_name' => config('app.name')]),
                'loginUrl' => route('filament.app.auth.login'),
                'bookingLeadDays' => Appointment::BOOKING_LEAD_DAYS,
                'cancellationHours' => Appointment::CANCELLATION_WINDOW_HOURS,
                'rescheduleHours' => Appointment::RESCHEDULE_WINDOW_HOURS,
            ],
        );
    }
}
