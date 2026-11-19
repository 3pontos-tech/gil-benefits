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
use TresPontosTech\Billing\Core\Actions\ResolveBenefitAccess;
use TresPontosTech\Billing\Core\Enums\BenefitAccessEnum;
use TresPontosTech\Company\Models\Company;

/**
 * O passo a passo até a primeira consultoria (FLM-107). As boas-vindas entregam o acesso; este
 * e-mail conduz o colaborador até o agendamento. Os prazos citados saem das mesmas constantes que
 * o agendamento usa, para o texto não mentir quando a regra mudar.
 */
class EmployeeOnboardingMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /**
     * O listener do onboarding roda antes do de boas-vindas, e a etapa 1 manda procurar as
     * boas-vindas: a espera garante que elas cheguem primeiro, por qualquer porta de entrada.
     */
    public const int DELAY_MINUTES = 5;

    /**
     * @param  bool  $hasTemporaryPassword  a empresa ou a importação criou a senha, que chegou nas
     *                                      boas-vindas; no auto cadastro a pessoa já escolheu a dela.
     */
    public function __construct(
        public readonly User $user,
        public readonly bool $hasTemporaryPassword,
    ) {
        $this->onQueue('emails')->afterCommit()->delay(now()->addMinutes(self::DELAY_MINUTES));
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
                'needsPlan' => $this->needsPlan(),
                'welcomeSubject' => __('user::mail.welcome.subject', ['app_name' => config('app.name')]),
                'loginUrl' => route('filament.app.auth.login'),
                'bookingLeadDays' => Appointment::BOOKING_LEAD_DAYS,
                'cancellationHours' => Appointment::CANCELLATION_WINDOW_HOURS,
                'rescheduleHours' => Appointment::RESCHEDULE_WINDOW_HOURS,
            ],
        );
    }

    /**
     * Quem ainda precisa de plano cai na vitrine no primeiro acesso, então o texto não promete
     * acesso e a etapa 1 avisa da escolha. A pergunta é feita no envio, e não na criação: o voucher
     * do cadastro só é resgatado depois do UserRegistered, e quem assina durante a espera já
     * recebe o texto de quem tem plano.
     */
    private function needsPlan(): bool
    {
        $company = Company::query()->find($this->user->employerCompanyId());

        return $company instanceof Company
            && resolve(ResolveBenefitAccess::class)->for($this->user, $company) === BenefitAccessEnum::PersonalPlanRequired;
    }
}
