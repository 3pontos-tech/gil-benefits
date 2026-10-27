<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Appointments;

use App\Models\Users\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use TresPontosTech\Api\Actions\Employee\Appointments\ListEmployeeAppointmentsAction;
use TresPontosTech\Api\Enums\Employee\Appointments\AppointmentListFilter;
use TresPontosTech\Api\Http\Requests\V1\Employee\Appointments\CancelAppointmentRequest;
use TresPontosTech\Api\Http\Requests\V1\Employee\Appointments\ListAppointmentsRequest;
use TresPontosTech\Api\Http\Requests\V1\Employee\Appointments\RescheduleAppointmentRequest;
use TresPontosTech\Api\Http\Requests\V1\Employee\Appointments\StoreAppointmentRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\Appointments\AppointmentResource;
use TresPontosTech\Appointments\Actions\CancelAppointmentForUserAction;
use TresPontosTech\Appointments\Actions\RescheduleAppointmentForUserAction;
use TresPontosTech\Appointments\Actions\ScheduleAppointmentForUserAction;
use TresPontosTech\Appointments\Enums\CancelImpact;
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

    public function store(StoreAppointmentRequest $request, ScheduleAppointmentForUserAction $schedule): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $appointment = $schedule->handle(
            $user,
            $request->string('category_type')->toString(),
            $request->string('appointment_at')->toString(),
            $request->filled('notes') ? $request->string('notes')->toString() : null,
        );

        return new AppointmentResource($appointment->load(AppointmentResource::EAGER_LOADS))
            ->response($request)
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Consultor ocupado no novo horário sai do encontro, que volta `pending` com `consultant: null`.
     *
     * @throws Throwable
     */
    public function update(RescheduleAppointmentRequest $request, string $appointment, RescheduleAppointmentForUserAction $reschedule): AppointmentResource
    {
        /** @var User $user */
        $user = $request->user();

        $outcome = $reschedule->handle(
            $this->ownedAppointment($request, $appointment),
            $user,
            $request->string('appointment_at')->toString(),
        );

        return new AppointmentResource($outcome->appointment->load(AppointmentResource::EAGER_LOADS));
    }

    /**
     * Cancela sem apagar: o encontro continua e responde 200 com o status resultante (`cancelled` ou
     * `cancelled_late`), que é o que o app deve exibir. Com `expected_impact: returns_credit` e o prazo
     * de 4 h já passado, responde 422 em `expected_impact` e não cancela: o app pergunta de novo,
     * avisando que o crédito será perdido.
     */
    public function cancel(CancelAppointmentRequest $request, string $appointment, CancelAppointmentForUserAction $cancel): AppointmentResource
    {
        /** @var User $user */
        $user = $request->user();

        $cancelled = $cancel->handle(
            // Sem as relações: a action relê o encontro com a linha travada e o resource as carrega
            // uma vez só, já com o estado cancelado.
            $this->ownedAppointment($request, $appointment, with: []),
            $user,
            $request->enum('expected_impact', CancelImpact::class),
        );

        return new AppointmentResource($cancelled->load(AppointmentResource::EAGER_LOADS));
    }

    /**
     * @param  list<string>  $with
     */
    private function ownedAppointment(Request $request, string $id, array $with = AppointmentResource::EAGER_LOADS): Appointment
    {
        /** @var User $user */
        $user = $request->user();

        return $user->appointments()->with($with)->findOrFail($id);
    }
}
