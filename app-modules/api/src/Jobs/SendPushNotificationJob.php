<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Jobs;

use App\Models\Users\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Api\Actions\Employee\Auth\ResolveAppAccessAction;
use TresPontosTech\Api\DTOs\Push\ExpoPushMessage;
use TresPontosTech\Api\DTOs\Push\ExpoPushTicket;
use TresPontosTech\Api\Enums\ExpoPushError;
use TresPontosTech\Api\Exceptions\PushConfigurationException;
use TresPontosTech\Api\Models\PushTicket;
use TresPontosTech\Api\Models\PushToken;
use TresPontosTech\Api\Support\ExpoPushClient;

/**
 * Entrega um push em todos os aparelhos da pessoa. Roda depois do commit de quem o causou, para
 * o app não buscar um estado que ainda não existe, e confere o acesso ao app na hora do envio:
 * quem perdeu o benefício entre o evento e o envio não recebe nada.
 *
 * A Expo fora do ar não pode levar um aviso urgente embora: o job tenta de novo por uns 20
 * minutos e desiste sozinho quando o aviso passa da validade. A entrega é "pelo menos uma vez",
 * como a da própria Expo: se a resposta se perder depois de a Expo aceitar, a pessoa pode receber
 * o aviso duas vezes, o que é melhor que não receber. Um 4xx (pedido malformado) não se repete.
 */
final class SendPushNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(
        public readonly string $userId,
        public readonly ExpoPushMessage $message,
    ) {
        $this->onQueue((string) config('api.push.queue'));
        $this->afterCommit();
    }

    /**
     * Segundos até cada nova tentativa.
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120, 600, 600];
    }

    public function handle(ExpoPushClient $client, ResolveAppAccessAction $resolveAppAccess): void
    {
        if ($this->message->hasExpired()) {
            return;
        }

        $user = User::query()->find($this->userId);

        if (! $user instanceof User || ! $resolveAppAccess->handle($user)->isGranted()) {
            return;
        }

        $tokens = PushToken::query()->where('user_id', $user->getKey())->get();
        $accepted = 0;

        foreach ($tokens->chunk((int) config('api.push.messages_per_request')) as $chunk) {
            $chunk = $chunk->values();
            $tickets = $this->send($client, array_values($chunk->map(fn (PushToken $token): string => $token->token)->all()));

            if ($tickets === null) {
                return;
            }

            foreach ($chunk as $index => $token) {
                $accepted += $this->record($token, $tickets[$index]) ? 1 : 0;
            }
        }

        Log::info('push.sent', ['type' => $this->message->type(), 'devices' => $tokens->count(), 'accepted' => $accepted]);
    }

    /**
     * Um 4xx que não é 429 é pedido malformado: tentar de novo não resolve, e o job falha de vez
     * (devolve null).
     *
     * @param  list<string>  $tokens
     * @return list<ExpoPushTicket>|null
     */
    private function send(ExpoPushClient $client, array $tokens): ?array
    {
        try {
            return $client->send($this->message, $tokens);
        } catch (RequestException $requestException) {
            if ($requestException->response->clientError() && $requestException->response->status() !== 429) {
                $this->fail($requestException);

                return null;
            }

            throw $requestException;
        }
    }

    /**
     * Ticket aceito vai esperar o recibo. Aparelho que não existe mais sai na hora. Erro de
     * configuração é relatado; o resto vai para o log só com o código. Um token apagado durante o
     * envio (a pessoa saiu da conta) não tem ticket a guardar, e não interrompe os outros aparelhos.
     */
    private function record(PushToken $token, ExpoPushTicket $ticket): bool
    {
        if ($ticket->isAccepted()) {
            try {
                PushTicket::query()->create(['id' => $ticket->id, 'push_token_id' => $token->getKey(), 'created_at' => now()]);
            } catch (QueryException) {
                Log::debug('push.ticket_skipped', ['reason' => 'token removed during send']);
            }

            return true;
        }

        $error = $ticket->error();

        if ($error === ExpoPushError::DeviceNotRegistered) {
            $token->delete();
        } elseif ($error?->isConfiguration() === true) {
            report(PushConfigurationException::from($error, 'ticket'));
        } else {
            Log::warning('push.ticket_error', ['error' => $ticket->errorCode, 'platform' => $token->platform->value]);
        }

        return false;
    }
}
