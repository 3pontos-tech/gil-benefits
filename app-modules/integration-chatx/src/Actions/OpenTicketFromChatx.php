<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Actions;

use Illuminate\Database\QueryException;
use TresPontosTech\IntegrationChatx\DTO\ChatxTicketDTO;
use TresPontosTech\IntegrationChatx\Support\ChatxCategoryMap;
use TresPontosTech\Support\Actions\CreateSupportTicketAction;
use TresPontosTech\Support\DTOs\CreateSupportTicketDTO;
use TresPontosTech\Support\DTOs\TicketOriginDTO;
use TresPontosTech\Support\Enums\TicketOriginSourceEnum;
use TresPontosTech\Support\Models\SupportTicket;
use TresPontosTech\Support\Models\TicketOrigin;

/**
 * Opens a support ticket from a ChatX payload, at most once per external reference.
 */
final class OpenTicketFromChatx
{
    public function __construct(
        private readonly ResolveChatxRequester $requesters,
        private readonly CreateSupportTicketAction $createTicket,
    ) {}

    /**
     * Returns the ticket and whether this call is the one that created it, so the
     * controller can answer 201 or 200.
     *
     * Idempotency runs in two layers. The lookup catches the ordinary repeat — ChatX
     * retrying a delivery it never saw acknowledged — without touching the database
     * for writes. The UNIQUE on (source, external_reference) catches the case the
     * lookup cannot: two deliveries in flight at the same time, both finding nothing.
     * The loser of that race lands here as a QueryException, and by then the winner's
     * row is committed, so re-reading answers with it.
     *
     * @return array{ticket: SupportTicket, created: bool}
     */
    public function execute(ChatxTicketDTO $dto): array
    {
        $existing = $this->findByReference($dto->externalReference);

        if ($existing instanceof SupportTicket) {
            return ['ticket' => $existing, 'created' => false];
        }

        // Resolved before creating: an unrecognised requester must not produce a
        // ticket, and the exception it throws renders as a 422.
        $user = $this->requesters->execute($dto);

        try {
            $ticket = $this->createTicket->execute(new CreateSupportTicketDTO(
                category: ChatxCategoryMap::toCategory($dto->category),
                subject: $dto->subject,
                description: $dto->description,
                userId: $user->id,
                companyId: $user->detail?->company_id,
                visitorName: $dto->visitorName,
                visitorEmail: $dto->visitorEmail,
                visitorCompanyName: $dto->visitorCompanyName,
                environment: TicketOriginSourceEnum::Chatx->value,
                origin: new TicketOriginDTO(
                    source: TicketOriginSourceEnum::Chatx,
                    externalReference: $dto->externalReference,
                ),
            ));
        } catch (QueryException $queryException) {
            $winner = $this->findByReference($dto->externalReference);

            // Nothing to hand back means the failure was not the reference clash we
            // are guarding against — let it surface instead of masking a real fault.
            throw_unless($winner instanceof SupportTicket, $queryException);

            return ['ticket' => $winner, 'created' => false];
        }

        return ['ticket' => $ticket, 'created' => true];
    }

    private function findByReference(string $reference): ?SupportTicket
    {
        $ticketId = TicketOrigin::query()
            ->where('source', TicketOriginSourceEnum::Chatx)
            ->where('external_reference', $reference)
            ->value('support_ticket_id');

        if (! is_string($ticketId)) {
            return null;
        }

        // withoutGlobalScopes(): tickets carry Filament's company tenancy scope, and
        // an API request has no tenant to satisfy it. Scoped, this lookup would find
        // nothing and the endpoint would open a duplicate on every retry.
        return SupportTicket::query()
            ->withoutGlobalScopes()
            ->find($ticketId);
    }
}
