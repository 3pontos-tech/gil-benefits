<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Auth;

use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LogoutController
{
    /**
     * Revoga só o token deste aparelho; os outros dispositivos continuam conectados.
     */
    public function __invoke(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $user->currentAccessToken()->delete();

        return response()->noContent();
    }
}
