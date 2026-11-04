<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Auth;

use Illuminate\Http\Response;
use TresPontosTech\Api\Actions\Employee\Auth\ResetPasswordWithCodeAction;
use TresPontosTech\Api\Http\Requests\V1\Employee\Auth\ResetPasswordRequest;

class ResetPasswordController
{
    /**
     * Não devolve token: o app volta ao login e entra com a senha nova, e o login continua sendo o
     * único lugar que decide quem entra.
     */
    public function __invoke(ResetPasswordRequest $request, ResetPasswordWithCodeAction $resetPassword): Response
    {
        $resetPassword->handle(
            email: $request->string('email')->toString(),
            resetToken: $request->string('reset_token')->toString(),
            password: $request->string('password')->toString(),
        );

        return response()->noContent();
    }
}
