<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Appointments;

use App\Models\Users\User;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Api\Http\Requests\V1\Employee\Appointments\StoreFeedbackRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\Appointments\AppointmentResource;
use TresPontosTech\Appointments\Actions\SubmitAppointmentFeedbackAction;

class FeedbackController
{
    public function __invoke(StoreFeedbackRequest $request, string $appointment, SubmitAppointmentFeedbackAction $submit): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $owned = $user->appointments()->with(AppointmentResource::EAGER_LOADS)->findOrFail($appointment);

        $submit->handle(
            $owned,
            $user,
            $request->integer('rating'),
            $request->filled('comment') ? $request->string('comment')->toString() : null,
        );

        return new AppointmentResource($owned->load(AppointmentResource::EAGER_LOADS))
            ->response($request)
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
