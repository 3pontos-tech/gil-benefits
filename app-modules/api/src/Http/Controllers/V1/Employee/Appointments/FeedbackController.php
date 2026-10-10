<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Appointments;

use App\Models\Users\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Api\Http\Requests\V1\Employee\StoreFeedbackRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\AppointmentResource;
use TresPontosTech\Appointments\Actions\SubmitAppointmentFeedbackAction;
use TresPontosTech\Appointments\Exceptions\AppointmentFeedbackException;

class FeedbackController
{
    /**
     * Encontro alheio é 404; consultoria não concluída ou já avaliada volta em `appointment`.
     */
    public function __invoke(StoreFeedbackRequest $request, string $appointment, SubmitAppointmentFeedbackAction $submit): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $owned = $user->appointments()->with(AppointmentResource::EAGER_LOADS)->findOrFail($appointment);

        try {
            $submit->handle(
                $owned,
                $user,
                $request->integer('rating'),
                $request->filled('comment') ? $request->string('comment')->toString() : null,
            );
        } catch (AppointmentFeedbackException $appointmentFeedbackException) {
            throw ValidationException::withMessages(['appointment' => $appointmentFeedbackException->getMessage()]);
        }

        return new AppointmentResource($owned->load(AppointmentResource::EAGER_LOADS))
            ->response($request)
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
