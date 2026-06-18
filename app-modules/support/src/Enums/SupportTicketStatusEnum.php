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
    case Dispatched = 'dispatched';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function getLabel(): string|Htmlable|null
    {
        return __('support::enums.ticket_status.' . $this->value);
    }

    /**
     * Derives the ticket status from its destinations' outcomes. Delivery failure
     * lives on the destination, not the ticket — so a failed send keeps the ticket
     * pending (the job retries), it does not move the ticket to a failed state.
     *
     * @param  iterable<TicketDestinationStatusEnum>  $statuses
     */
    public static function fromDestinations(iterable $statuses): self
    {
        $statuses = collect($statuses);

        return $statuses->contains(TicketDestinationStatusEnum::Sent)
            ? self::Dispatched
            : self::Pending;
    }

    /**
     * The states this status may transition to (manual transitions). A resolved ticket can
     * be reopened back into progress; Closed is terminal.
     *
     * @return array<SupportTicketStatusEnum>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Dispatched, self::Closed],
            self::Dispatched => [self::InProgress, self::Closed],
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
            self::Dispatched => Heroicon::PaperAirplane,
            self::InProgress => Heroicon::Cog6Tooth,
            self::Resolved => Heroicon::CheckCircle,
            self::Closed => Heroicon::ArchiveBox,
        };
    }

    public function getColor(): array|string
    {
        return match ($this) {
            self::Pending => Color::Amber,
            self::Dispatched => Color::Blue,
            self::InProgress => Color::Indigo,
            self::Resolved => Color::Green,
            self::Closed => Color::Gray,
        };
    }
}
