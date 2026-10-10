<?php

declare(strict_types=1);

namespace TresPontosTech\Appointments\Actions;

use App\Models\Users\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
     * Checagem e criação rodam numa transação que trava a linha do usuário (`lockForUpdate`).
     * Dois pedidos simultâneos da mesma pessoa (duplo clique, duplo toque, reenvio do app)
     * passam um de cada vez: o segundo só confere o saldo depois que o primeiro gravou, e é
     * recusado em vez de criar um encontro sem cota ou crédito correspondente (#288). A
     * checagem usa o usuário recarregado sob a trava, porque `monthly_appointments_left`
     * fica memoizado na instância recebida.
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
        return DB::transaction(function () use ($user, $categoryType, $appointmentAt, $notes): Appointment {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if (! $lockedUser->canCreateAppointment()) {
                throw BookingBlockedException::because(BookingBlockReasons::for($lockedUser));
            }

            $slotAt = $this->bookableSlots->parse($appointmentAt);

            throw_unless($slotAt instanceof Carbon, SlotUnavailableException::class);

            return $this->bookAppointment->handle(new BookAppointmentDTO(
                userId: $lockedUser->getKey(),
                categoryType: AppointmentCategoryEnum::from($categoryType),
                appointmentAt: $slotAt,
                notes: $notes,
            ));
        });
    }
}
