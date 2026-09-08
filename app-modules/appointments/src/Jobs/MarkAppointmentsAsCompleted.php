<?php

namespace TresPontosTech\Appointments\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Queue\Queueable;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\AppointmentStatus;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Models\Appointment;

/**
 * Encerra diariamente as consultas cuja data já passou e que ninguém fechou.
 *
 * São dois desfechos, e a diferença importa: uma consulta confirmada que venceu foi
 * atendida, então conclui e o crédito é gasto; uma que venceu ainda esperando confirmação
 * nunca aconteceu, então cancela pelo sistema e o crédito volta. Concluir a segunda
 * mentiria nas métricas de atendimento e cobraria por uma consultoria que não houve.
 *
 * Fechar as duas é o que solta o cliente: enquanto a consulta consta em aberto,
 * `User::hasOngoingAppointment()` o impede de marcar outra, e não há saída pela interface
 * — cancelar consulta com data passada também é recusado.
 */
class MarkAppointmentsAsCompleted implements ShouldQueue
{
    use Queueable;

    /** Folga depois da consulta antes de considerá-la encerrada. */
    private const GRACE_DAYS = 1;

    public function handle(): void
    {
        $this->completeAttended();
        $this->cancelNeverConfirmed();
    }

    /**
     * Confirmada e vencida: aconteceu. O consultor é exigido porque é ele que caracteriza
     * o atendimento — sem ele não há o que concluir.
     */
    private function completeAttended(): void
    {
        $this->stale(AppointmentStatus::Active)
            ->whereNotNull('consultant_id')
            ->chunkById(100, function ($appointments): void {
                foreach ($appointments as $appointment) {
                    $appointment->current_transition->handle(new TransitionData);
                }
            });
    }

    /**
     * Vencida ainda em `pending`: ninguém confirmou e a consultoria não houve. Cancela como
     * sistema, o que devolve a consulta a quem marcou — e nunca como cancelamento tardio,
     * já que a falha não foi do cliente.
     */
    private function cancelNeverConfirmed(): void
    {
        $this->stale(AppointmentStatus::Pending)
            ->chunkById(100, function ($appointments): void {
                foreach ($appointments as $appointment) {
                    $appointment->current_transition->handle(
                        new TransitionData(cancellationActor: CancellationActor::System)
                    );
                }
            });
    }

    /**
     * @return Builder<Appointment>
     */
    private function stale(AppointmentStatus $status): Builder
    {
        return Appointment::query()
            ->where('status', $status)
            ->where('appointment_at', '<', now()->subDays(self::GRACE_DAYS));
    }
}
