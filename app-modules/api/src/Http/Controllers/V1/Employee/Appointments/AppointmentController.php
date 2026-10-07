<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Appointments;

use App\Models\Users\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use TresPontosTech\Api\Actions\V1\Employee\ListEmployeeAppointmentsAction;
use TresPontosTech\Api\Enums\AppointmentListFilter;
use TresPontosTech\Api\Http\Requests\V1\Employee\ListAppointmentsRequest;
use TresPontosTech\Api\Http\Requests\V1\Employee\RescheduleAppointmentRequest;
use TresPontosTech\Api\Http\Requests\V1\Employee\StoreAppointmentRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\AppointmentResource;
use TresPontosTech\Appointments\Actions\CancelAppointmentForUserAction;
use TresPontosTech\Appointments\Actions\RescheduleAppointmentForUserAction;
use TresPontosTech\Appointments\Actions\ScheduleAppointmentForUserAction;
use TresPontosTech\Appointments\Exceptions\AppointmentStateException;
use TresPontosTech\Appointments\Exceptions\BookingBlockedException;
use TresPontosTech\Appointments\Exceptions\SlotUnavailableException;
use TresPontosTech\Appointments\Models\Appointment;

class AppointmentController
{
    public function index(ListAppointmentsRequest $request, ListEmployeeAppointmentsAction $list): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        return AppointmentResource::collection($list->handle($user, $request->enum('status', AppointmentListFilter::class), AppointmentResource::EAGER_LOADS));
    }

    public function show(Request $request, string $appointment): AppointmentResource
    {
        return new AppointmentResource($this->ownedAppointment($request, $appointment));
    }

    /**
     * Sem saldo ou com consultoria em aberto volta em `credit`; horário fora da oferta em `appointment_at`.
     */
    public function store(StoreAppointmentRequest $request, ScheduleAppointmentForUserAction $schedule): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $appointment = $schedule->handle(
                $user,
                $request->string('category_type')->toString(),
                $request->string('appointment_at')->toString(),
                $request->filled('notes') ? $request->string('notes')->toString() : null,
            );
        } catch (BookingBlockedException $bookingBlockedException) {
            throw ValidationException::withMessages(['credit' => $bookingBlockedException->reasons]);
        } catch (SlotUnavailableException $slotUnavailableException) {
            throw ValidationException::withMessages(['appointment_at' => $slotUnavailableException->getMessage()]);
        }

        return new AppointmentResource($appointment->load(AppointmentResource::EAGER_LOADS))
            ->response($request)
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Janela fechada volta em `appointment`; horário fora da oferta ou consultor ocupado em `appointment_at`.
     *
     * @throws Throwable
     */
    public function update(RescheduleAppointmentRequest $request, string $appointment, RescheduleAppointmentForUserAction $reschedule): AppointmentResource
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $outcome = $reschedule->handle(
                $this->ownedAppointment($request, $appointment),
                $user,
                $request->string('appointment_at')->toString(),
            );
        } catch (AppointmentStateException $appointmentStateException) {
            throw ValidationException::withMessages(['appointment' => $appointmentStateException->getMessage()]);
        } catch (SlotUnavailableException $slotUnavailableException) {
            throw ValidationException::withMessages(['appointment_at' => $slotUnavailableException->getMessage()]);
        }

        return new AppointmentResource($outcome->appointment->load(AppointmentResource::EAGER_LOADS));
    }

    /**
     * Encontro passado ou já encerrado volta em `appointment`. Responde 200 com o status resultante
     * (`cancelled` ou `cancelled_late`), que é o que o app deve exibir.
     */
    public function destroy(Request $request, string $appointment, CancelAppointmentForUserAction $cancel): AppointmentResource
    {
        /** @var User $user */
        $user = $request->user();

        try {
            $cancelled = $cancel->handle($this->ownedAppointment($request, $appointment), $user);
        } catch (AppointmentStateException $appointmentStateException) {
            throw ValidationException::withMessages(['appointment' => $appointmentStateException->getMessage()]);
        }

        return new AppointmentResource($cancelled->load(AppointmentResource::EAGER_LOADS));
    }

    private function ownedAppointment(Request $request, string $id): Appointment
    {
        /** @var User $user */
        $user = $request->user();

        return $user->appointments()->with(AppointmentResource::EAGER_LOADS)->findOrFail($id);
    }
}
