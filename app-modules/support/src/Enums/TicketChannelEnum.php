<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Where the ticket was opened. Unlike TicketOrigin, which only exists for tickets
 * that carry an external reference, every ticket has a channel.
 */
enum TicketChannelEnum: string implements HasIcon, HasLabel
{
    case PanelApp = 'panel_app';
    case PanelCompany = 'panel_company';
    case PanelConsultant = 'panel_consultant';
    case Api = 'api';
    case HelpCenter = 'help_center';
    case Chatx = 'chatx';

    public function getLabel(): string
    {
        return __('support::enums.ticket_channel.' . $this->value);
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::PanelApp, self::PanelCompany, self::PanelConsultant => Heroicon::ComputerDesktop,
            self::Api => Heroicon::DevicePhoneMobile,
            self::HelpCenter => Heroicon::GlobeAlt,
            self::Chatx => Heroicon::ChatBubbleLeftRight,
        };
    }
}
