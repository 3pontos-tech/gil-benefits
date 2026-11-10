<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Jobs;

use App\Models\Users\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Api\Actions\Employee\Auth\ResolveAppAccessAction;
use TresPontosTech\Api\Models\PushTicket;
use TresPontosTech\Api\Models\PushToken;
use TresPontosTech\Api\Support\ExpoPushClient;

/**
 * Entrega um push em todos os aparelhos da pessoa. Roda depois do commit de quem o causou, para
 * o app não buscar um estado que ainda não existe, e confere o acesso ao app na hora do envio:
 * quem perdeu o benefício entre o evento e o envio não recebe nada.
 *
 * Uma tentativa só: o cliente já repete o que é transitório, e repetir o job inteiro mandaria o
 * mesmo aviso duas vezes para quem já recebeu.
 */
final class SendPushNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    /**
     * @param  array<string, mixed>  $message  a mensagem da Expo sem o destinatário
     */
    public function __construct(
        public readonly string $userId,
        public readonly array $message,
    ) {
        $this->afterCommit();
    }

    public function handle(ExpoPushClient $client, ResolveAppAccessAction $resolveAppAccess): void
    {
        $user = User::query()->find($this->userId);

        if (! $user instanceof User || ! $resolveAppAccess->handle($user)->isGranted()) {
            return;
        }

        $tokens = PushToken::query()->where('user_id', $user->getKey())->get();

        foreach ($tokens->chunk((int) config('api.push.messages_per_request')) as $chunk) {
            $chunk = $chunk->values();
            $tickets = $client->send(array_values($chunk->map(fn (PushToken $token): array => ['to' => $token->token, ...$this->message])->all()));

            foreach ($chunk as $index => $token) {
                $this->record($token, $tickets[$index] ?? []);
            }
        }
    }

    /**
     * Ticket aceito vai esperar o recibo. Aparelho que não existe mais sai na hora. O resto vai
     * para o log só com o código do erro: a mensagem da Expo repete o token.
     *
     * @param  array<string, mixed>  $ticket
     */
    private function record(PushToken $token, array $ticket): void
    {
        $ticketId = $ticket['id'] ?? null;

        if (($ticket['status'] ?? null) === 'ok' && is_string($ticketId)) {
            PushTicket::query()->create(['id' => $ticketId, 'push_token_id' => $token->getKey(), 'created_at' => now()]);

            return;
        }

        $error = data_get($ticket, 'details.error');

        if ($error === 'DeviceNotRegistered') {
            $token->delete();

            return;
        }

        Log::warning('push.ticket_error', ['error' => $error, 'platform' => $token->platform->value]);
    }
}
