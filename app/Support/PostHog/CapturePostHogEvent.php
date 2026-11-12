<?php

declare(strict_types=1);

namespace App\Support\PostHog;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class CapturePostHogEvent implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<string, string|int|bool>  $properties
     */
    public function __construct(
        public readonly string $distinctId,
        public readonly string $event,
        public readonly array $properties,
        public readonly string $timestamp,
        public readonly string $uuid,
    ) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    public function handle(): void
    {
        $key = config('services.posthog.key');
        if (blank($key)) {
            return;
        }

        Http::baseUrl(config()->string('services.posthog.host'))
            ->timeout(10)
            ->post('/i/v0/e/', [
                'api_key' => $key,
                'event' => $this->event,
                'distinct_id' => $this->distinctId,
                'properties' => $this->properties,
                'timestamp' => $this->timestamp,
                'uuid' => $this->uuid,
            ])
            ->throw();
    }
}
