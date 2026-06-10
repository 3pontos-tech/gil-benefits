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
        return match ($this) {
            self::SupportTi => TicketDestinationTypeEnum::Monday,
            default => TicketDestinationTypeEnum::Email,
        };
    }

    public function getRecipientEmail(): ?string
    {
        return match ($this) {
            self::Financial => config('support.emails.financial'),
            self::Commercial => config('support.emails.commercial'),
            self::Cs => config('support.emails.cs'),
            self::Product => config('support.emails.product'),
            default => null,
        };
    }
}
