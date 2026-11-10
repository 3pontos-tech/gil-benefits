<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Http\Controllers\V1\Employee\Me;

use App\Models\Users\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;
use TresPontosTech\Api\Actions\Employee\Me\RegisterPushTokenAction;
use TresPontosTech\Api\Enums\PushPlatform;
use TresPontosTech\Api\Http\Requests\V1\Employee\Me\UpdatePushTokenRequest;
use TresPontosTech\Api\Models\PushToken;

/**
 * O endereço de push deste aparelho. O app envia ao entrar, ao abrir e quando a Expo troca o
 * token; e apaga quando a pessoa desliga os avisos. Sair da conta apaga sozinho, junto com o
 * token de acesso.
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
            $this->session($request),
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
        PushToken::query()->where('personal_access_token_id', $this->session($request)->getKey())->delete();

        return response()->noContent();
    }

    /**
     * A sessão é o token Bearer que o app recebeu no login; o token de push se prende a ela.
     */
    private function session(Request $request): PersonalAccessToken
    {
        $session = PersonalAccessToken::findToken((string) $request->bearerToken());

        abort_unless($session instanceof PersonalAccessToken, Response::HTTP_FORBIDDEN);

        return $session;
    }
}
