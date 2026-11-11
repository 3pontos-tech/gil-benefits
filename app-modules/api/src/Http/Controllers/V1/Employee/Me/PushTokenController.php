<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Me;

use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TresPontosTech\Api\Actions\Employee\Me\RegisterPushTokenAction;
use TresPontosTech\Api\Enums\PushPlatform;
use TresPontosTech\Api\Http\Requests\V1\Employee\Me\UpdatePushTokenRequest;
use TresPontosTech\Api\Models\PushToken;

/**
 * O endereço de push deste aparelho. O app envia ao entrar, ao abrir e quando a Expo troca o
 * token; e apaga quando a pessoa desliga os avisos. Ele fica preso à sessão, o token de acesso
 * desta requisição: sair da conta apaga sozinho, junto com ela.
 */
class PushTokenController
{
    public function __construct(private readonly RegisterPushTokenAction $registerPushToken) {}

    /**
     * Idempotente: enviar o mesmo token de novo responde 204 sem mudar nada.
     */
    public function update(UpdatePushTokenRequest $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $this->registerPushToken->handle(
            $user,
            $user->currentAccessToken(),
            $request->string('token')->toString(),
            PushPlatform::from($request->string('platform')->toString()),
        );

        return response()->noContent();
    }

    /**
     * Idempotente: apagar sem token registrado também responde 204.
     */
    public function destroy(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        PushToken::query()->where('personal_access_token_id', $user->currentAccessToken()->getKey())->delete();

        return response()->noContent();
    }
}
