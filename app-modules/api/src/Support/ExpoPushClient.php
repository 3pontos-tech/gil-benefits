<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Cliente da Expo Push API. Tenta de novo o que uma nova tentativa resolve (conexão, 429 e erro
 * do lado deles) e nunca um 400, que é pedido malformado.
 */
final readonly class ExpoPushClient
{
    /**
     * Envia um lote. A Expo responde um ticket por mensagem, na mesma ordem.
     *
     * @param  list<array<string, mixed>>  $messages
     * @return list<array<string, mixed>>
     */
    public function send(array $messages): array
    {
        /** @var list<array<string, mixed>> $tickets */
        $tickets = $this->request()->post((string) config('api.push.send_url'), $messages)->throw()->json('data', []);

        return $tickets;
    }

    /**
     * Recibos dos tickets, indexados pelo id do ticket. Um id ainda sem recibo não vem.
     *
     * @param  list<string>  $ticketIds
     * @return array<string, array<string, mixed>>
     */
    public function receipts(array $ticketIds): array
    {
        /** @var array<string, array<string, mixed>> $receipts */
        $receipts = $this->request()->post((string) config('api.push.receipts_url'), ['ids' => $ticketIds])->throw()->json('data', []);

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
