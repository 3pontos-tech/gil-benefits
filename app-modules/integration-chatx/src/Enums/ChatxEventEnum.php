<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Enums;

/**
 * Anything else ChatX sends is refused at validation rather than silently treated
 * as one of these.
 */
enum ChatxEventEnum: string
{
    case TicketCreated = 'ticket.created';
    case ReopenRequested = 'ticket.reopen_requested';
    case ResolutionConfirmed = 'ticket.resolution_confirmed';
}
