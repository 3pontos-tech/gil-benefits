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

    /**
     * Held only while the work runs, so a crash that skips the release expires in
     * minutes instead of burning the key for a whole day. Must outlive $timeout.
     */
    private const int CLAIM_TTL_MINUTES = 5;

    private const int PROCESSED_TTL_HOURS = 24;

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

        try {
            $action->handle($dto);
        } catch (Throwable $throwable) {
            // The claim marks work in progress, not work done: keeping it after a
            // failure would make attempts 2 and 3 return early and report success,
            // losing the activation with no dead-letter job to show for it.
            $this->release($dto);

            throw $throwable;
        }

        $this->confirm($dto);
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
        $key = $this->cacheKey($dto);

        if ($key === null) {
            return true;
        }

        return Cache::add($key, false, now()->addMinutes(self::CLAIM_TTL_MINUTES));
    }

    private function release(VirtuWebhookDTO $dto): void
    {
        $key = $this->cacheKey($dto);

        if ($key !== null) {
            Cache::forget($key);
        }
    }

    private function confirm(VirtuWebhookDTO $dto): void
    {
        $key = $this->cacheKey($dto);

        if ($key !== null) {
            Cache::put($key, true, now()->addHours(self::PROCESSED_TTL_HOURS));
        }
    }

    private function cacheKey(VirtuWebhookDTO $dto): ?string
    {
        return blank($dto->idempotencyKey) ? null : 'virtu:webhook:' . $dto->idempotencyKey;
    }
}
