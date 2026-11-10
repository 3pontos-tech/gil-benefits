<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Api\DTOs\Push\ExpoPushReceipt;
use TresPontosTech\Api\Enums\ExpoPushError;
use TresPontosTech\Api\Exceptions\PushConfigurationException;
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
     * Sem recibo ainda, o ticket espera a próxima rodada. Com recibo, sai. Um aparelho que não
     * existe mais leva o token junto, a não ser que o app o tenha registrado de novo depois do
     * envio (reinstalar no iPhone devolve o mesmo token). Erro de configuração (chave FCM,
     * credenciais) é relatado; o resto vai para o log só com o código.
     */
    private function settle(PushTicket $ticket, ?ExpoPushReceipt $receipt): void
    {
        if (! $receipt instanceof ExpoPushReceipt) {
            return;
        }

        $error = $receipt->error();

        if ($error === ExpoPushError::DeviceNotRegistered) {
            PushToken::query()
                ->whereKey($ticket->push_token_id)
                ->where('updated_at', '<=', $ticket->created_at)
                ->delete();
        } elseif ($error?->isConfiguration() === true) {
            report(PushConfigurationException::from($error, 'receipt'));
        } elseif (! $receipt->delivered) {
            Log::warning('push.receipt_error', ['error' => $receipt->errorCode]);
        }

        $ticket->delete();
    }
}
