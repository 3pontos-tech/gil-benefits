<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use Illuminate\Support\Carbon;
use TresPontosTech\Appointments\DTO\BookAppointmentDTO;
use TresPontosTech\Appointments\Enums\AppointmentCategoryEnum;
use TresPontosTech\Appointments\Exceptions\BookingBlockedException;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;
use TresPontosTech\Appointments\Support\BookableSlots;
use TresPontosTech\Appointments\Support\BookingBlockReasons;

final readonly class ScheduleAppointmentForUserAction
{
    public function __construct(
        private BookableSlots $bookableSlots,
        private BookAppointmentAction $bookAppointment,
    ) {}

    /**
     * Agenda uma consultoria para o colaborador com a régua do painel, nesta ordem: ele precisa
     * poder agendar (cota ou crédito e nenhuma consultoria em aberto), o horário precisa estar
     * entre os oferecidos (antecedência mínima e disponibilidade real) e só então a reserva é
     * criada como pendente, debitando cota ou prendendo um crédito. Não atribui consultor.
     *
     * `$appointmentAt` e `$categoryType` chegam como o cliente enviou; valor malformado conta como
     * horário indisponível, e categoria inválida lança ValueError como hoje (BookAppointmentDTO).
     *
     * @throws BookingBlockedException
     * @throws SlotUnavailableException
     */
    public function handle(
        User $user,
        string $categoryType,
        ?string $appointmentAt,
        ?string $notes = null,
    ): Appointment {
        if (! $user->canCreateAppointment()) {
            throw BookingBlockedException::because(BookingBlockReasons::for($user));
        }

        $slotAt = $this->bookableSlots->parse($appointmentAt);

        throw_unless($slotAt instanceof Carbon, SlotUnavailableException::class);

        return $this->bookAppointment->handle(new BookAppointmentDTO(
            userId: $user->getKey(),
            categoryType: AppointmentCategoryEnum::from($categoryType),
            appointmentAt: $slotAt,
            notes: $notes,
        ));
    }
}
