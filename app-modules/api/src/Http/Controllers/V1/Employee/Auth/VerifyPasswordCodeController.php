<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Auth;

use Illuminate\Http\JsonResponse;
use TresPontosTech\Api\Actions\Employee\Auth\VerifyPasswordResetCodeAction;
use TresPontosTech\Api\Http\Requests\V1\Employee\Auth\VerifyPasswordCodeRequest;
use TresPontosTech\Api\Support\ApiDates;

class VerifyPasswordCodeController
{
    public function __invoke(VerifyPasswordCodeRequest $request, VerifyPasswordResetCodeAction $verifyCode): JsonResponse
    {
        $grant = $verifyCode->handle(
            email: $request->string('email')->toString(),
            code: $request->string('code')->toString(),
        );

        return response()->json([
            'data' => [
                'reset_token' => $grant->plainTextToken,
                'expires_at' => ApiDates::dateTime($grant->expiresAt),
            ],
        ]);
    }
}
