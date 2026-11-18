<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Http\Controllers;

use App\Enums\InboundWebhookSourceEnum;
use Basement\Webhooks\Actions\StoreInboundWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use TresPontosTech\IntegrationChatx\Actions\AnswerResolutionFromChatx;
use TresPontosTech\IntegrationChatx\Actions\OpenTicketFromChatx;
use TresPontosTech\IntegrationChatx\DTO\ChatxResolutionAnswerDTO;
use TresPontosTech\IntegrationChatx\DTO\ChatxTicketDTO;
use TresPontosTech\IntegrationChatx\Enums\ChatxEventEnum;
use TresPontosTech\IntegrationChatx\Http\Requests\ChatxWebhookRequest;

/**
 * Endpoint for tickets opened over WhatsApp through ChatX, and for the customer's
 * answer to the resolved message (reopen or confirm).
 *
 * Unlike the Monday and Barte webhooks, this one does its work inside the request
 * instead of queueing a job. It has to: the agreed contract answers with the ticket
 * id and protocol so the bot can tell the customer their protocol number in the
 * same conversation, and with a 422 when the requester is unknown. Neither answer
 * exists yet if the work is deferred; the same goes for the answers, which reply with
 * the status the ticket ended up in. The expensive part — routing the ticket to its
 * sector, e-mail and Monday — is still queued, inside CreateSupportTicketAction.
 *
 * The stored delivery masks the CPF and phone, keeping only their last digits: it is
 * kept for debugging, where telling two deliveries apart is enough, and should not
 * hold identity documents. The customer's reopen message is masked whole: it is free
 * text that may carry anything, and it is already kept as the reason in the history.
 */
final class ChatxTicketController
{
    public function __invoke(ChatxWebhookRequest $request): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = $request->validated();

        resolve(StoreInboundWebhook::class)->store(
            source: InboundWebhookSourceEnum::Chatx,
            event: is_string($payload['event'] ?? null) ? $payload['event'] : 'unknown',
            url: $request->url(),
            payload: $this->masked($payload),
        );

        return $request->event() === ChatxEventEnum::TicketCreated
            ? $this->open($payload)
            : $this->answer($payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function open(array $payload): JsonResponse
    {
        $dto = ChatxTicketDTO::fromArray($payload);

        ['ticket' => $ticket, 'created' => $created] = resolve(OpenTicketFromChatx::class)->execute($dto);

        return response()->json([
            'ticket_id' => $ticket->id,
            'protocol' => $ticket->protocol,
            'external_reference' => $dto->externalReference,
        ], $created ? Response::HTTP_CREATED : Response::HTTP_OK);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function answer(array $payload): JsonResponse
    {
        $ticket = resolve(AnswerResolutionFromChatx::class)->execute(ChatxResolutionAnswerDTO::fromArray($payload));

        return response()->json([
            'ticket_id' => $ticket->id,
            'protocol' => $ticket->protocol,
            'status' => $ticket->status->value,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function masked(array $payload): array
    {
        foreach (['visitor.document' => 2, 'visitor.phone' => 4, 'message' => 0] as $key => $visible) {
            $value = Arr::get($payload, $key);

            if (is_string($value) && $value !== '') {
                Arr::set($payload, $key, Str::mask($value, '*', 0, max(0, mb_strlen($value) - $visible)));
            }
        }

        return $payload;
    }
}
