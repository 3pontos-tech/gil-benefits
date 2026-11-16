<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Support;

use Illuminate\Support\Str;
use TresPontosTech\Support\Enums\SupportTicketStatusEnum;
use TresPontosTech\Support\Models\SupportTicket;

final class ChatxMessage
{
    private const string LOCALE = 'pt_BR';

    public static function created(SupportTicket $ticket): string
    {
        return self::render('created', $ticket);
    }

    public static function statusChanged(SupportTicket $ticket, SupportTicketStatusEnum $from, SupportTicketStatusEnum $to): ?string
    {
        $key = match ($to) {
            SupportTicketStatusEnum::InProgress => $from === SupportTicketStatusEnum::Resolved ? 'reopened' : 'in_progress',
            SupportTicketStatusEnum::Resolved => 'resolved',
            SupportTicketStatusEnum::Closed => 'closed',
            SupportTicketStatusEnum::Pending => null,
        };

        return $key === null ? null : self::render($key, $ticket);
    }

    private static function render(string $key, SupportTicket $ticket): string
    {
        $firstName = Str::of((string) $ticket->getRequesterName())->squish()->before(' ')->toString();

        $greeting = $firstName === ''
            ? __('integration-chatx::messages.greeting_anonymous', locale: self::LOCALE)
            : __('integration-chatx::messages.greeting', ['name' => $firstName], self::LOCALE);

        return __('integration-chatx::messages.' . $key, [
            'greeting' => $greeting,
            'protocol' => $ticket->protocol,
            'subject' => Str::squish($ticket->subject),
        ], self::LOCALE);
    }
}
