<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Me;

use App\Models\Users\User;
use Illuminate\Http\Response;
use TresPontosTech\Api\Http\Requests\V1\Employee\UpdatePasswordRequest;
use TresPontosTech\User\Actions\ChangeUserPasswordAction;

/**
 * Troca a senha; os outros aparelhos são desconectados pelo UserObserver, e este continua.
 */
class PasswordController
{
    public function __invoke(UpdatePasswordRequest $request, ChangeUserPasswordAction $changePassword): Response
    {
        /** @var User $user */
        $user = $request->user();

        $changePassword->handle($user, $request->string('password')->toString());

        return response()->noContent();
    }
}
