<?php

namespace TresPontosTech\Appointments\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use Zap\Enums\ScheduleTypes;
use Zap\Facades\Zap;
use Zap\Models\Schedule;

readonly class AssignConsultantAction
{
    /**
     * Reserva o horário do encontro na agenda do consultor, recusando se ele estiver ocupado.
     *
     * Antes de conferir a disponibilidade, a transação trava as linhas de disponibilidade do
     * consultor que cobrem o dia (`lockForUpdate`). Duas operações simultâneas sobre o mesmo
     * consultor (atribuição no admin e reagendamento pelo colaborador, por exemplo) passam a
     * conferir e reservar uma de cada vez, e a segunda vê o horário já ocupado (#289). A agenda
     * cadastrada no admin é recorrente e sem data de fim, por isso `end_date` nulo também conta;
     * `whereDate` compara só a data, igual no Postgres e no SQLite dos testes.
     *
     * @throws SlotUnavailableException
     */
    public function handle(Appointment $appointment): void
    {
        if (blank($appointment->consultant_id)) {
            return;
        }

        DB::transaction(function () use ($appointment): void {
            Schedule::query()
                ->where('schedule_type', ScheduleTypes::APPOINTMENT)
                ->whereJsonContains('metadata->appointment_id', $appointment->id)
                ->delete();

            $consultant = $appointment->consultant;

            $day = $appointment->appointment_at->toDateString();

            Schedule::query()
                ->where('schedulable_type', $consultant->getMorphClass())
                ->where('schedulable_id', $consultant->getKey())
                ->whereDate('start_date', '<=', $day)
                ->where(fn (Builder $query): Builder => $query
                    ->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $day))
                ->lockForUpdate()
                ->get();

            $isAvailable = $consultant->isBookableAtTime(
                $appointment->appointment_at->format('Y-m-d'),
                $appointment->appointment_at->format('H:i'),
                $appointment->appointment_at->copy()->addHour()->format('H:i'),
            );

            throw_unless($isAvailable, SlotUnavailableException::class);

            Zap::for($consultant)
                ->named(sprintf('Appointment #%s - %s', $appointment->id, $appointment->user->name))
                ->appointment()
                ->from($appointment->appointment_at->toDateString())
                ->to($appointment->appointment_at->copy()->addDay()->toDateString())
                ->addPeriod(
                    $appointment->appointment_at->format('H:i'),
                    $appointment->appointment_at->copy()->addHour()->format('H:i'),
                )
                ->withMetadata(['appointment_id' => $appointment->id])
                ->save();
        });
    }
}
