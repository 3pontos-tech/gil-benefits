<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use TresPontosTech\Appointments\Actions\Transitions\TransitionData;
use TresPontosTech\Appointments\Enums\CancellationActor;
use TresPontosTech\Appointments\Exceptions\AppointmentStateException;
use TresPontosTech\Appointments\Models\Appointment;

final readonly class CancelAppointmentForUserAction
{
    /**
     * Cancela o agendamento do próprio colaborador pela máquina de estados, como ator User: dentro
     * das 4 horas vira cancelled_late e o crédito é consumido; antes disso vira cancelled e o crédito
     * (ou a cota) volta. Checa canBeCancelled() antes para nunca deixar vazar a
     * InvalidTransitionException de encontro passado ou já encerrado.
     *
     * @throws AppointmentStateException
     */
    public function handle(Appointment $appointment, User $user): Appointment
    {
        throw_unless(
            $appointment->user_id === $user->getKey() && $appointment->canBeCancelled(),
            AppointmentStateException::cannotCancel(),
        );

        $appointment->current_transition->handle(new TransitionData(
            cancellationActor: CancellationActor::User,
            cancelledBy: $user,
        ));

        return $appointment;
    }
}
