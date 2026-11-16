<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Http\Controllers;

use App\Enums\InboundWebhookSourceEnum;
use Basement\Webhooks\Actions\StoreInboundWebhook;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\IntegrationChatx\Actions\OpenTicketFromChatx;
use TresPontosTech\IntegrationChatx\DTO\ChatxTicketDTO;
use TresPontosTech\IntegrationChatx\Http\Requests\OpenTicketRequest;

/**
 * Intake endpoint for tickets opened over WhatsApp through ChatX.
 *
 * Unlike the Monday and Barte webhooks, this one does its work inside the request
 * instead of queueing a job. It has to: the agreed contract answers with the ticket
 * id and protocol so the bot can tell the customer their protocol number in the
 * same conversation, and with a 422 when the requester is unknown. Neither answer
 * exists yet if the work is deferred. The expensive part — routing the ticket to
 * its sector, e-mail and Monday — is still queued, inside CreateSupportTicketAction.
 */
final class ChatxTicketController
{
    public function __invoke(OpenTicketRequest $request, OpenTicketFromChatx $openTicket): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->validated();

        resolve(StoreInboundWebhook::class)->store(
            source: InboundWebhookSourceEnum::Chatx,
            event: is_string($payload['event'] ?? null) ? $payload['event'] : 'unknown',
            url: $request->url(),
            payload: $payload,
        );

        $dto = ChatxTicketDTO::fromArray($payload);

        ['ticket' => $ticket, 'created' => $created] = $openTicket->execute($dto);

        return response()->json([
            'ticket_id' => $ticket->id,
            'protocol' => $ticket->protocol,
            'external_reference' => $dto->externalReference,
        ], $created ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
