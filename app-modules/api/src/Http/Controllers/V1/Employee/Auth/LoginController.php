<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Auth;

use Illuminate\Http\JsonResponse;
use TresPontosTech\Api\Actions\Employee\Auth\AuthenticateEmployeeAction;
use TresPontosTech\Api\Actions\Employee\Me\BuildEmployeeProfileAction;
use TresPontosTech\Api\Http\Requests\V1\Employee\Auth\LoginRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\Me\MeResource;

class LoginController
{
    public function __invoke(
        LoginRequest $request,
        AuthenticateEmployeeAction $authenticate,
        BuildEmployeeProfileAction $buildProfile,
    ): JsonResponse {
        $session = $authenticate->handle(
            email: $request->string('email')->toString(),
            password: $request->string('password')->toString(),
            deviceName: $request->string('device_name')->toString(),
        );

        return response()->json([
            'data' => [
                'token' => $session->plainTextToken,
                'user' => new MeResource($buildProfile->handle($session->user))->resolve($request),
            ],
        ]);
    }
}
