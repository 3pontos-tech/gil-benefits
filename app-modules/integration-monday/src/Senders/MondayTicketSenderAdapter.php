<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Senders;

use Illuminate\Support\Facades\Storage;
use Throwable;
use TresPontosTech\IntegrationMonday\MondayClient;
use TresPontosTech\IntegrationMonday\Support\MondayStatusMap;
use TresPontosTech\Support\Contracts\TicketChannelSender;
use TresPontosTech\Support\DTOs\DispatchResult;
use TresPontosTech\Support\Enums\TicketDestinationChannelEnum;
use TresPontosTech\Support\Models\SupportTicket;

/**
 * Adapter: implements the support TicketChannelSender port using the Monday
 * client. Builds the board column values from the ticket and creates the card;
 * the returned item id is persisted by the support orchestrator as the
 * destination's reference_id.
 */
final class MondayTicketSenderAdapter implements TicketChannelSender
{
    public function __construct(
        private readonly MondayClient $client,
    ) {}

    public function send(SupportTicket $ticket, TicketDestinationChannelEnum $channel): DispatchResult
    {
        $columns = (array) config('monday.columns');

        try {
            $response = $this->client->createItem(
                boardId: (string) config('monday.board_id'),
                groupId: (string) config('monday.group_id'),
                itemName: sprintf('[%s] %s', $ticket->protocol, $ticket->subject),
                columnValues: [
                    $columns['status'] => ['index' => MondayStatusMap::index($ticket->status)],
                    $columns['protocol'] => $ticket->protocol,
                    $columns['category'] => (string) $ticket->category->getLabel(),
                    $columns['requester'] => $ticket->getRequesterEmail() ?? '',
                    $columns['description'] => ['text' => $ticket->description],
                ],
            );
        } catch (Throwable $throwable) {
            return DispatchResult::failed($throwable->getMessage());
        }

        $this->uploadAttachments($ticket, $response->itemId, (string) $columns['attachments']);

        return DispatchResult::sent($response->itemId);
    }

    /**
     * Best-effort upload of the ticket's attachments (images/PDFs) to the card's
     * file column. A failed upload is logged but does not fail the dispatch —
     * the card already exists and its status is what the lifecycle tracks.
     */
    private function uploadAttachments(SupportTicket $ticket, string $itemId, string $columnId): void
    {
        foreach ($ticket->getMedia('attachments') as $media) {
            try {
                $this->client->addFileToColumn(
                    $itemId,
                    $columnId,
                    (string) Storage::disk($media->disk)->get($media->getPathRelativeToRoot()),
                    $media->file_name,
                );
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
