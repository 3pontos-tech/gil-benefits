<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum TicketDestinationChannelEnum: string implements HasLabel
{
    case SupportTi = 'support_ti';
    case Financial = 'financial';
    case Commercial = 'commercial';
    case Cs = 'cs';
    case Product = 'product';

    public function getLabel(): string|Htmlable|null
    {
        return __('support::enums.destination_channel.' . $this->value);
    }

    public function getDestinationType(): TicketDestinationTypeEnum
    {
        // The support/TI channel is tracked on the Monday board when that
        // integration is configured; otherwise — and for every other channel —
        // it is delivered by e-mail.
        return $this === self::SupportTi && filled(config('monday.board_id'))
            ? TicketDestinationTypeEnum::Monday
            : TicketDestinationTypeEnum::Email;
    }

    public function getRecipientEmail(): ?string
    {
        return match ($this) {
            self::SupportTi => config('support.emails.support_ti'),
            self::Financial => config('support.emails.financial'),
            self::Commercial => config('support.emails.commercial'),
            self::Cs => config('support.emails.cs'),
            self::Product => config('support.emails.product'),
        };
    }
}
