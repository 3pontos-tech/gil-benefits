<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Throwable;
use TresPontosTech\Appointments\DTO\RescheduledAppointment;
use TresPontosTech\Appointments\Enums\AppointmentHistoryActor;
use TresPontosTech\Appointments\Exceptions\AppointmentStateException;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Support\BookableSlots;
use TresPontosTech\Consultants\Models\Consultant;

final readonly class RescheduleAppointmentForUserAction
{
    public function __construct(private BookableSlots $bookableSlots) {}

    /**
     * Move o agendamento do próprio colaborador para um horário oferecido. O consultor atual é
     * mantido quando está livre no novo horário; ocupado, ele sai do encontro, que volta para
     * Pending até a equipe atribuir outro. A sincronização da agenda (histórico, Zap, Google
     * Calendar) é a mesma do painel admin; se o consultor perder o horário entre a checagem e o
     * bloqueio ela reverte o registro e relança SlotUnavailableException; qualquer outra falha é
     * revertida aqui e relançada, sem reportar: quem chama decide (o painel reporta e avisa o
     * usuário; na API o handler reporta uma única vez).
     *
     * SyncAppointmentScheduleAction é resolvida no uso, e não no construtor, porque os testes do
     * painel a substituem por uma classe anônima via app()->instance().
     *
     * @throws AppointmentStateException quando o encontro não é do usuário ou a janela de 4 h fechou
     * @throws SlotUnavailableException
     * @throws Throwable
     */
    public function handle(Appointment $appointment, User $user, ?string $appointmentAt): RescheduledAppointment
    {
        throw_unless(
            $appointment->user_id === $user->getKey() && $appointment->canBeRescheduled(),
            AppointmentStateException::cannotReschedule(),
        );

        $newAppointmentAt = $this->bookableSlots->parse($appointmentAt);

        throw_unless($newAppointmentAt instanceof Carbon, SlotUnavailableException::class);

        $previousAppointmentAt = $appointment->appointment_at;
        $previousConsultantId = $appointment->consultant_id;

        $appointment->update([
            'appointment_at' => $newAppointmentAt,
            'consultant_id' => $this->keepsConsultant($appointment, $newAppointmentAt) ? $previousConsultantId : null,
        ]);

        try {
            $calendarSynced = resolve(SyncAppointmentScheduleAction::class)
                ->handle($appointment, $previousConsultantId, $previousAppointmentAt, AppointmentHistoryActor::User);
        } catch (SlotUnavailableException $slotUnavailableException) {
            throw $slotUnavailableException;
        } catch (Throwable $throwable) {
            $appointment->update([
                'appointment_at' => $previousAppointmentAt,
                'consultant_id' => $previousConsultantId,
            ]);

            throw $throwable;
        }

        return new RescheduledAppointment($appointment->refresh(), $calendarSynced);
    }

    /**
     * No mesmo horário o próprio bloqueio do encontro faria o consultor parecer ocupado.
     */
    private function keepsConsultant(Appointment $appointment, CarbonInterface $slotAt): bool
    {
        if ($appointment->appointment_at->equalTo($slotAt)) {
            return true;
        }

        $consultant = $appointment->loadMissing('consultant')->consultant;

        return $consultant instanceof Consultant && $consultant->isBookableAtTime(
            $slotAt->format('Y-m-d'),
            $slotAt->format('H:i'),
            $slotAt->copy()->addHour()->format('H:i'),
        );
    }
}
