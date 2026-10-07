<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Auth;

use App\Models\Users\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use TresPontosTech\Api\Http\Requests\V1\Employee\LoginRequest;
use TresPontosTech\Api\Http\Resources\V1\Employee\MeResource;

class LoginController
{
    /**
     * Troca e-mail e senha por um token do aparelho.
     *
     * Credencial inválida e conta fora do público respondem 422 em `email`, que é onde o
     * app mostra o erro. O e-mail é comparado sem diferenciar maiúsculas porque o teclado
     * do celular costuma capitalizar a primeira letra. Cada aparelho guarda um token só:
     * entrar de novo com o mesmo `device_name` substitui o anterior. O evento `Login`
     * alimenta o `RecordLastLogin`, como no login do painel.
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->whereRaw('lower(email) = ?', [$request->string('email')->lower()->toString()])
            ->first();

        if (! $user instanceof User || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages(['email' => __('api::auth.failed')]);
        }

        if (! $user->canUseApp()) {
            throw ValidationException::withMessages(['email' => __('api::auth.no_access')]);
        }

        $deviceName = $request->string('device_name')->toString();

        $user->tokens()->where('name', $deviceName)->delete();

        $token = $user->createToken(
            $deviceName,
            ['employee'],
            now()->addDays((int) config('api.token_ttl_days')),
        );

        event(new Login('sanctum', $user, false));

        return response()->json([
            'data' => [
                'token' => $token->plainTextToken,
                'user' => MeResource::make($user->refresh())->resolve($request),
            ],
        ]);
    }
}
