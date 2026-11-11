<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions;

use TresPontosTech\Appointments\DTO\AppointmentSnapshot;
use TresPontosTech\Appointments\Enums\AppointmentChange;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Notifications\AppointmentChangedNotification;

/**
 * Avisa a pessoa quando a equipe mudou um encontro já confirmado (gil-benefits#349). Toda tela ou
 * rotina da equipe que edita um encontro chama esta ação com o "antes" e o "depois". Fica fora da
 * sincronização da agenda porque confirmar e o reagendamento feito pela própria pessoa também
 * passam por ela, e nenhum dos dois é mudança a avisar. Funcionário excluído não tem a quem
 * avisar, e o save do admin segue.
 */
final readonly class NotifyAppointmentChangedAction
{
    public function handle(AppointmentSnapshot $before, Appointment $after, AppointmentHistoryActor $actor): void
    {
        if ($actor !== AppointmentHistoryActor::Admin) {
            return;
        }

        $change = AppointmentChange::between($before, $after);

        if ($change instanceof AppointmentChange) {
            $after->user?->notify(new AppointmentChangedNotification($after, $change));
        }
    }
}
