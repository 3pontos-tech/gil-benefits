<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Services\TicketRouterService;

class DispatchSupportTicketJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly SupportTicket $ticket,
    ) {}

    public function handle(TicketRouterService $router): void
    {
        $router->dispatch($this->ticket);
    }

    public function failed(Throwable $e): void
    {
        $this->ticket->update([
            'status' => SupportTicketStatusEnum::Failed,
        ]);
    }
}
