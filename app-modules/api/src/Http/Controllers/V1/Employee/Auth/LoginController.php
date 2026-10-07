<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use TresPontosTech\Api\Actions\V1\Employee\AuthenticateEmployeeAction;
use TresPontosTech\Api\Exceptions\EmployeeLoginException;
use TresPontosTech\Api\Http\Requests\V1\Employee\LoginRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\MeResource;

class LoginController
{
    /**
     * Falhas de login voltam como 422 em `email`, que é onde o app mostra o erro.
     */
    public function __invoke(LoginRequest $request, AuthenticateEmployeeAction $authenticate): JsonResponse
    {
        try {
            $session = $authenticate->handle(
                email: $request->string('email')->toString(),
                password: $request->string('password')->toString(),
                deviceName: $request->string('device_name')->toString(),
            );
        } catch (EmployeeLoginException $employeeLoginException) {
            throw ValidationException::withMessages(['email' => $employeeLoginException->getMessage()]);
        }

        return response()->json([
            'data' => [
                'token' => $session->plainTextToken,
                'user' => MeResource::make($session->user)->resolve($request),
            ],
        ]);
    }
}
