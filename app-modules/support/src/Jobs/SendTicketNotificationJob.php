<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;
use TresPontosTech\Support\DTOs\TicketNotificationResult;
use TresPontosTech\Support\Enums\TicketNotificationStatusEnum;
use TresPontosTech\Support\Exceptions\TicketNotificationFailedException;
use TresPontosTech\Support\Models\SupportTicketNotification;
use TresPontosTech\Support\TicketNotificationManager;

class SendTicketNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public int $timeout = 30;

    public function __construct(
        public readonly string $notificationId,
    ) {}

    public function handle(TicketNotificationManager $channels): void
    {
        $notification = SupportTicketNotification::query()->with('ticket')->find($this->notificationId);

        if ($notification === null || ! $notification->isPending()) {
            return;
        }

        try {
            $result = $channels->channel($notification->channel)->send($notification);
        } catch (Throwable $throwable) {
            $result = TicketNotificationResult::failed(null, $throwable->getMessage());
        }

        match ($result->status) {
            TicketNotificationStatusEnum::Sent => $notification->markSent((int) $result->responseCode),
            TicketNotificationStatusEnum::Skipped => $notification->markSkipped((string) $result->error),
            default => $notification->recordFailedAttempt($result->responseCode, (string) $result->error),
        };

        throw_if(
            $result->status === TicketNotificationStatusEnum::Failed,
            TicketNotificationFailedException::for($notification),
        );
    }

    public function failed(?Throwable $exception): void
    {
        SupportTicketNotification::query()->whereKey($this->notificationId)->failPending();
    }
}
