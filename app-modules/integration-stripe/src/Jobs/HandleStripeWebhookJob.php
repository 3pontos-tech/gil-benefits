<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationStripe\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;
use TresPontosTech\IntegrationStripe\Actions\HandleStripeWebhook;
use TresPontosTech\IntegrationStripe\DTO\StripeWebhookDTO;
use TresPontosTech\IntegrationStripe\Events\StripeWebhookFailed;

class HandleStripeWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Held only while the work runs, so a crash that skips the release expires in minutes
     * instead of burning the event id for a whole day. Must outlive $timeout.
     */
    private const int CLAIM_TTL_MINUTES = 5;

    private const int PROCESSED_TTL_HOURS = 24;

    public int $tries = 5;

    /**
     * Spread over about 20 minutes, so a short Stripe outage or a subscription lock held by
     * another event does not burn every attempt within seconds.
     *
     * @var list<int>
     */
    public array $backoff = [10, 60, 300, 900];

    public int $timeout = 60;

    public function __construct(
        public readonly StripeWebhookDTO $dto,
    ) {}

    /**
     * Stripe redelivers the same event id, so the id is claimed before the work and kept
     * only when the work succeeded: a failed attempt releases it for the retry.
     */
    public function handle(HandleStripeWebhook $action): void
    {
        $key = 'stripe:webhook:' . $this->dto->eventId;

        if (! Cache::add($key, false, now()->addMinutes(self::CLAIM_TTL_MINUTES))) {
            Log::info('Stripe webhook já processado, ignorando.', ['event_id' => $this->dto->eventId]);

            return;
        }

        try {
            $action->handle($this->dto);
        } catch (Throwable $throwable) {
            Cache::forget($key);

            throw $throwable;
        }

        Cache::put($key, true, now()->addHours(self::PROCESSED_TTL_HOURS));
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Stripe webhook job failed.', [
            'event_id' => $this->dto->eventId,
            'type' => $this->dto->type,
            'exception' => $exception->getMessage(),
        ]);

        event(new StripeWebhookFailed($this->dto->eventId, $this->dto->type));
    }
}
