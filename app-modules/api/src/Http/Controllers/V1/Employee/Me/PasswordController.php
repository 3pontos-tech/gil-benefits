<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Me;

use App\Models\Users\User;
use Illuminate\Http\Response;
use TresPontosTech\Api\Http\Requests\V1\Employee\Me\UpdatePasswordRequest;
use TresPontosTech\User\Actions\ChangeUserPasswordAction;

class PasswordController
{
    public function __invoke(UpdatePasswordRequest $request, ChangeUserPasswordAction $changePassword): Response
    {
        /** @var User $user */
        $user = $request->user();

        $changePassword->handle($user, $request->string('password')->toString(), $user->currentAccessToken());

        return response()->noContent();
    }
}
