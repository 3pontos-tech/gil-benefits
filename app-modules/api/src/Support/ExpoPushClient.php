<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;
use TresPontosTech\Api\DTOs\Push\ExpoPushMessage;
use TresPontosTech\Api\DTOs\Push\ExpoPushReceipt;
use TresPontosTech\Api\DTOs\Push\ExpoPushTicket;
use UnexpectedValueException;

/**
 * Cliente da Expo Push API. Tenta de novo o que uma nova tentativa resolve (conexão, 429 e erro
 * do lado deles) e nunca um 400, que é pedido malformado. O formato da Expo fica aqui e nos DTOs.
 */
final readonly class ExpoPushClient
{
    /**
     * Envia a mesma mensagem a até 100 aparelhos. A Expo responde um ticket por aparelho, na
     * mesma ordem; uma resposta de outro tamanho é recusada, para nenhum ticket ir para o token
     * errado.
     *
     * @param  list<string>  $tokens
     * @return list<ExpoPushTicket>
     */
    public function send(ExpoPushMessage $message, array $tokens): array
    {
        $payload = array_map(fn (string $token): array => $message->toExpoPayload($token), $tokens);
        $data = $this->request()->post((string) config('api.push.send_url'), $payload)->throw()->json('data');

        throw_unless(is_array($data) && count($data) === count($tokens), UnexpectedValueException::class, 'Expo answered a different number of tickets than messages sent.');

        return array_map(fn (mixed $ticket): ExpoPushTicket => ExpoPushTicket::fromExpo(is_array($ticket) ? $ticket : []), array_values($data));
    }

    /**
     * Recibos dos tickets, indexados pelo id do ticket. Um id ainda sem recibo não vem.
     *
     * @param  list<string>  $ticketIds
     * @return array<string, ExpoPushReceipt>
     */
    public function receipts(array $ticketIds): array
    {
        $data = $this->request()->post((string) config('api.push.receipts_url'), ['ids' => $ticketIds])->throw()->json('data');

        $receipts = [];

        foreach (is_array($data) ? $data : [] as $ticketId => $receipt) {
            $receipts[(string) $ticketId] = ExpoPushReceipt::fromExpo(is_array($receipt) ? $receipt : []);
        }

        return $receipts;
    }

    private function request(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->timeout(15)
            ->retry(3, fn (int $attempt): int => 500 * 2 ** $attempt, fn (Throwable $exception): bool => $this->isTransient($exception));

        $accessToken = config('api.push.access_token');

        return is_string($accessToken) && $accessToken !== '' ? $request->withToken($accessToken) : $request;
    }

    private function isTransient(Throwable $exception): bool
    {
        if ($exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof RequestException
            && ($exception->response->status() === 429 || $exception->response->serverError());
    }
}
