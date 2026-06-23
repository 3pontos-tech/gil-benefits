<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Enums;

use Filament\Support\Contracts\HasLabel;

enum TicketDestinationChannelEnum: string implements HasLabel
{
    case SupportTi = 'support_ti';
    case Financial = 'financial';
    case Commercial = 'commercial';
    case Cs = 'cs';
    case Product = 'product';

    public function getLabel(): string
    {
        return __('support::enums.destination_channel.' . $this->value);
    }

    /**
     * Delivery types for this channel. Every sector is notified by e-mail; the
     * support/TI sector is additionally mirrored as a card on the Monday board
     * (when that integration is configured).
     *
     * @return array<int, TicketDestinationTypeEnum>
     */
    public function getDestinationTypes(): array
    {
        $types = [TicketDestinationTypeEnum::Email];

        if ($this === self::SupportTi && filled(config('monday.board_id'))) {
            $types[] = TicketDestinationTypeEnum::Monday;
        }

        return $types;
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
