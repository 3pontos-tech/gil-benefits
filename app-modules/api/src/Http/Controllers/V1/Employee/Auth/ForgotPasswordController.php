<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Auth;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\Api\Actions\Employee\Auth\RequestPasswordResetCodeAction;
use TresPontosTech\Api\Http\Requests\V1\Employee\Auth\ForgotPasswordRequest;

class ForgotPasswordController
{
    /**
     * Sempre 202 com o mesmo corpo, tenha a conta, acesso ao app, espera do reenvio ou bloqueio:
     * a resposta não pode dizer quem tem conta.
     */
    public function __invoke(ForgotPasswordRequest $request, RequestPasswordResetCodeAction $requestCode): JsonResponse
    {
        $requestCode->handle($request->string('email')->toString());

        return response()->json(['message' => __('api::auth.password_code_sent')], Response::HTTP_ACCEPTED);
    }
}
