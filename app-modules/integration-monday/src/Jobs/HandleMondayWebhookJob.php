<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use TresPontosTech\IntegrationMonday\Actions\HandleMondayWebhook;
use TresPontosTech\IntegrationMonday\DTO\MondayWebhookDTO;

/**
 * Processes a Monday webhook off the request cycle. Keeps the controller fast
 * and lets delivery retry on transient failures.
 */
class HandleMondayWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly array $payload,
    ) {}

    public function handle(HandleMondayWebhook $action): void
    {
        $action->handle(MondayWebhookDTO::fromArray($this->payload));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Monday webhook job failed.', [
            'payload' => $this->payload,
            'exception' => $exception->getMessage(),
        ]);
    }
}
