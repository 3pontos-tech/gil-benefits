<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentReminderNotification;
use TresPontosTech\Appointments\Support\AppointmentReminder;

/**
 * Lembra a pessoa antes do encontro (gil-benefits#349). Roda a cada 5 minutos e pega os encontros
 * confirmados que começam na próxima hora e ainda não foram lembrados.
 *
 * Cada encontro é reservado com um update condicional antes do aviso: duas execuções ao mesmo
 * tempo, ou uma mudança de horário no meio da rodada, nunca geram dois lembretes nem um lembrete
 * do horário antigo. A reserva e o aviso ficam na mesma transação: se o aviso falhar antes do
 * commit (a busca do aparelho), a reserva se desfaz e a próxima rodada tenta de novo.
 * Funcionário excluído fica de fora. O horário mudado e a confirmação seguem a regra de
 * AppointmentReminder.
 */
class SendAppointmentRemindersJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    public function handle(): void
    {
        Appointment::query()
            ->with('user')
            ->whereHas('user')
            ->where('status', AppointmentStatus::Active)
            ->whereNotNull('consultant_id')
            ->whereNull('reminder_sent_at')
            ->where('appointment_at', '>', now())
            ->where('appointment_at', '<=', now()->addMinutes(AppointmentReminder::MINUTES_BEFORE))
            ->chunkById(100, function (Collection $appointments): void {
                /** @var Collection<int, Appointment> $appointments */
                foreach ($appointments as $appointment) {
                    DB::transaction(function () use ($appointment): void {
                        if ($this->claim($appointment)) {
                            $appointment->user->notify(new AppointmentReminderNotification($appointment));
                        }
                    });
                }
            });
    }

    /**
     * Marca o lembrete só se o encontro ainda está como foi lido: confirmado, sem lembrete e no
     * mesmo horário.
     */
    private function claim(Appointment $appointment): bool
    {
        return Appointment::query()
            ->whereKey($appointment->getKey())
            ->whereNull('reminder_sent_at')
            ->where('status', AppointmentStatus::Active)
            ->where('appointment_at', $appointment->appointment_at)
            ->update(['reminder_sent_at' => now()]) === 1;
    }
}
