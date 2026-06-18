<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum SupportTicketStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function getLabel(): string|Htmlable|null
    {
        return __('support::enums.ticket_status.' . $this->value);
    }

    /**
     * The states this status may transition to (manual transitions). A ticket opens as
     * Pending and is moved into progress by an agent; a resolved ticket can be reopened.
     * Closed is terminal — used when there was no response or the ticket doesn't apply.
     *
     * @return array<SupportTicketStatusEnum>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::InProgress, self::Closed],
            self::InProgress => [self::Resolved, self::Closed],
            self::Resolved => [self::InProgress, self::Closed],
            self::Closed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::InProgress => Heroicon::Cog6Tooth,
            self::Resolved => Heroicon::CheckCircle,
            self::Closed => Heroicon::ArchiveBox,
        };
    }

    public function getColor(): array|string
    {
        return match ($this) {
            self::Pending => Color::Amber,
            self::InProgress => Color::Indigo,
            self::Resolved => Color::Green,
            self::Closed => Color::Gray,
        };
    }
}
