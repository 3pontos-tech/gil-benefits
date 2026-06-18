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
        // Every channel is delivered by e-mail for now. When the Monday integration
        // lands, SupportTi will switch to TicketDestinationTypeEnum::Monday.
        return TicketDestinationTypeEnum::Email;
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
