<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationVirtu\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;
use TresPontosTech\IntegrationVirtu\Actions\HandleVirtuWebhook;
use TresPontosTech\IntegrationVirtu\DTO\VirtuWebhookDTO;

/**
 * Processes a Virtu webhook off the request cycle, so the controller answers fast
 * and transient failures get retried.
 */
class HandleVirtuWebhookJob implements ShouldQueue
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

    public function handle(HandleVirtuWebhook $action): void
    {
        $dto = VirtuWebhookDTO::fromArray($this->payload);

        if (! $this->claim($dto)) {
            Log::info('Virtu webhook já processado, ignorando.', ['idempotency_key' => $dto->idempotencyKey]);

            return;
        }

        $action->handle($dto);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Virtu webhook job failed.', [
            'payload' => $this->payload,
            'exception' => $exception->getMessage(),
        ]);
    }

    /**
     * Virtu sends an idempotency key both as a header and in the body, with a
     * semantic shape (transaction:SALE:1003:SUCCESS). Cache::add() only succeeds
     * for the first caller, so a redelivery is dropped instead of charging or
     * upserting twice.
     *
     * A payload without a key is always processed — better a duplicate than a
     * silently discarded charge.
     */
    private function claim(VirtuWebhookDTO $dto): bool
    {
        if (blank($dto->idempotencyKey)) {
            return true;
        }

        return Cache::add('virtu:webhook:' . $dto->idempotencyKey, true, now()->addDay());
    }
}
