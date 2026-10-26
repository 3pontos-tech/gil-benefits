<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\CancelImpact;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Exceptions\AppointmentStateException;
use TresPontosTech\Appointments\Exceptions\CancelImpactChangedException;
use TresPontosTech\Appointments\Models\Appointment;

final readonly class CancelAppointmentForUserAction
{
    /**
     * Cancela o agendamento do próprio colaborador pela máquina de estados, como ator User: dentro
     * das 4 horas vira cancelled_late e o crédito é consumido; antes disso vira cancelled e o crédito
     * (ou a cota) volta. Checa canBeCancelled() antes para nunca deixar vazar a
     * InvalidTransitionException de encontro passado ou já encerrado.
     *
     * A checagem e a transição rodam com a linha do encontro travada (`lockForUpdate`) e o
     * estado relido: dois cancelamentos simultâneos (duplo toque, repetição automática do app)
     * passam um de cada vez, e o segundo vê o encontro já cancelado e é recusado, em vez de
     * repetir avisos, e-mails e o job do Calendar. A instância recebida é a atualizada, porque o
     * painel mostra a confirmação a partir dela.
     *
     * `$expectedImpact` é o destino do crédito que a pessoa confirmou (o app manda o que o alerta
     * mostrou). Se ela confirmou com o crédito voltando e o prazo passou até o pedido chegar, nada é
     * cancelado: a conferência usa o mesmo relógio e a mesma linha travada da transição. Ter aceitado
     * perder o crédito nunca impede o cancelamento, porque devolver é sempre melhor que o combinado.
     *
     * @throws AppointmentStateException
     * @throws CancelImpactChangedException
     */
    public function handle(Appointment $appointment, User $user, ?CancelImpact $expectedImpact = null): Appointment
    {
        return DB::transaction(function () use ($appointment, $user, $expectedImpact): Appointment {
            Appointment::query()->whereKey($appointment->getKey())->lockForUpdate()->first();
            $appointment->refresh();

            throw_unless(
                $appointment->user_id === $user->getKey() && $appointment->canBeCancelled(),
                AppointmentStateException::cannotCancel(),
            );

            throw_if(
                $expectedImpact === CancelImpact::ReturnsCredit && $appointment->isLateCancellation(),
                CancelImpactChangedException::class,
            );

            $appointment->current_transition->handle(new TransitionData(
                cancellationActor: CancellationActor::User,
                cancelledBy: $user,
            ));

            return $appointment;
        });
    }
}
