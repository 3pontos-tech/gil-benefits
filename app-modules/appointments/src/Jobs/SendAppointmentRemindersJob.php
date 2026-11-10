<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentReminderNotification;

/**
 * Lembra a pessoa 1 h antes do encontro (gil-benefits#349). Roda a cada 5 minutos e pega os
 * encontros confirmados que começam na próxima hora e ainda não foram lembrados.
 *
 * A marca vem antes do envio: se o envio falhar, a pessoa fica sem lembrete, mas nunca recebe dois.
 * O horário mudado limpa a marca (SyncAppointmentScheduleAction), e confirmar a menos de 1 h do
 * encontro já marca, porque o aviso de confirmação acabou de dizer o horário.
 */
class SendAppointmentRemindersJob implements ShouldQueue
{
    use Queueable;

    public const int MINUTES_BEFORE = 60;

    public function handle(): void
    {
        Appointment::query()
            ->with('user')
            ->where('status', AppointmentStatus::Active)
            ->whereNotNull('consultant_id')
            ->whereNull('reminder_sent_at')
            ->where('appointment_at', '>', now())
            ->where('appointment_at', '<=', now()->addMinutes(self::MINUTES_BEFORE))
            ->chunkById(100, function (Collection $appointments): void {
                /** @var Collection<int, Appointment> $appointments */
                foreach ($appointments as $appointment) {
                    $appointment->forceFill(['reminder_sent_at' => now()])->save();
                    $appointment->user->notify(new AppointmentReminderNotification($appointment));
                }
            });
    }
}
