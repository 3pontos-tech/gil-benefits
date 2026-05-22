<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Barte\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use TresPontosTech\Billing\Barte\Actions\HandleBarteWebhook;
use TresPontosTech\Billing\Barte\DTOs\BarteWebhookDto;

class HandleBarteWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public readonly array $payload) {}

    public function handle(HandleBarteWebhook $action): void
    {
        $action->handle(BarteWebhookDto::fromArray($this->payload));
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Barte webhook job falhou', [
            'payload' => $this->payload,
            'exception' => $exception->getMessage(),
        ]);
    }
}
