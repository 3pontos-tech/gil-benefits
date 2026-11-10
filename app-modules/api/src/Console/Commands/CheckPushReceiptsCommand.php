<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Api\Models\PushTicket;
use TresPontosTech\Api\Models\PushToken;
use TresPontosTech\Api\Support\ExpoPushClient;

/**
 * Confere os recibos dos pushes aceitos. É o recibo que diz se o aparelho ainda existe: com
 * `DeviceNotRegistered` o token sai, para não mandar mais nada para ele. A Expo libera o recibo
 * em alguns minutos e o apaga em 24 h, então o ticket espera 15 minutos e some depois de um dia.
 */
final class CheckPushReceiptsCommand extends Command
{
    protected $signature = 'push:check-receipts';

    protected $description = 'Confere os recibos dos pushes e apaga os tokens de aparelhos que não existem mais';

    public function handle(ExpoPushClient $client): int
    {
        PushTicket::query()->where('created_at', '<', now()->subDay())->delete();

        PushTicket::query()
            ->where('created_at', '<=', now()->subMinutes(15))
            ->chunkById((int) config('api.push.receipts_per_request'), function (Collection $tickets) use ($client): void {
                /** @var Collection<int, PushTicket> $tickets */
                $receipts = $client->receipts(array_values($tickets->map(fn (PushTicket $ticket): string => $ticket->id)->all()));

                foreach ($tickets as $ticket) {
                    $this->settle($ticket, $receipts[$ticket->getKey()] ?? null);
                }
            });

        return self::SUCCESS;
    }

    /**
     * Sem recibo ainda, o ticket espera a próxima rodada. Com recibo, sai; e um erro que não é do
     * aparelho vai para o log só com o código, porque é configuração (chave FCM, credenciais).
     *
     * @param  array<string, mixed>|null  $receipt
     */
    private function settle(PushTicket $ticket, ?array $receipt): void
    {
        if ($receipt === null) {
            return;
        }

        $error = data_get($receipt, 'details.error');

        if ($error === 'DeviceNotRegistered') {
            PushToken::query()->whereKey($ticket->push_token_id)->delete();

            return;
        }

        if (($receipt['status'] ?? null) !== 'ok') {
            Log::error('push.receipt_error', ['error' => $error]);
        }

        $ticket->delete();
    }
}
