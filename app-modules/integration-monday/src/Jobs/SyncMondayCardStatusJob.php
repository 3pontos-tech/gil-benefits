<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Date;
use TresPontosTech\IntegrationMonday\DTO\ChangeColumnValuesDTO;
use TresPontosTech\IntegrationMonday\MondayClient;
use TresPontosTech\IntegrationMonday\Support\MondayStatusMap;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationStatusEnum;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\Models\TicketDestination;

/**
 * Pushes an app-side status change to the ticket's Monday card. No-op when the
 * ticket has no delivered Monday destination (e.g. non-TI channels).
 */
class SyncMondayCardStatusJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly string $ticketId,
        public readonly SupportTicketStatusEnum $status,
    ) {}

    public function handle(): void
    {
        $destination = TicketDestination::query()
            ->where('support_ticket_id', $this->ticketId)
            ->where('type', TicketDestinationTypeEnum::Monday)
            ->where('status', TicketDestinationStatusEnum::Sent)
            ->whereNotNull('reference_id')
            ->first();

        if ($destination === null) {
            return;
        }

        $now = Date::now();

        // Resolve the client only once we know there's a card to update, so the
        // job is a harmless no-op when Monday isn't configured. Mirror the status
        // and bump the board's "last updated" date in the same request.
        App::make(MondayClient::class)->changeColumnValues(new ChangeColumnValuesDTO(
            itemId: (string) $destination->reference_id,
            boardId: (string) config('monday.board_id'),
            columnValues: [
                (string) config('monday.columns.status') => ['index' => MondayStatusMap::index($this->status)],
                (string) config('monday.columns.updated_at') => ['date' => $now->format('Y-m-d'), 'time' => $now->format('H:i:s')],
            ],
        ));
    }
}
