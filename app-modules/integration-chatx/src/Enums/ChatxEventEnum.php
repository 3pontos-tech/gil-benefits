<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Enums;

/**
 * Events ChatX can send us. Only ticket creation is handled today; anything else
 * is refused at validation rather than silently treated as a creation.
 */
enum ChatxEventEnum: string
{
    case TicketCreated = 'ticket.created';
}
