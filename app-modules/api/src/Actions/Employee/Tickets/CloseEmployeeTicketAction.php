<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Tickets;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use TresPontosTech\Support\Actions\TransitionSupportTicketStatusAction;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Exceptions\InvalidTransitionException;
use TresPontosTech\Support\Models\SupportTicket;

final readonly class CloseEmployeeTicketAction
{
    public function __construct(
        private ListEmployeeTicketsAction $listTickets,
        private TransitionSupportTicketStatusAction $transition,
    ) {}

    /**
     * Finaliza um chamado da pessoa, como o "Finalizar" do painel. Chamado de outra pessoa não é
     * encontrado; já encerrado não pode mudar de status.
     *
     * @throws ModelNotFoundException<SupportTicket>
     * @throws InvalidTransitionException
     */
    public function handle(User $user, string $ticketId): SupportTicket
    {
        /** @var SupportTicket $ticket */
        $ticket = $this->listTickets->handle($user)->findOrFail($ticketId);

        return $this->transition->execute($ticket, SupportTicketStatusEnum::Closed);
    }
}
