<?php

declare(strict_types=1);

namespace TresPontosTech\Support\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum SupportTicketCategoryEnum: string implements HasColor, HasIcon, HasLabel
{
    case LoginAccess = 'login_access';
    case PlatformError = 'platform_error';
    case Bug = 'bug';
    case Integration = 'integration';
    case Performance = 'performance';
    case SchedulingIssue = 'scheduling_issue';
    case FinancialIssue = 'financial_issue';
    case ContractPlan = 'contract_plan';
    case CancellationComplaint = 'cancellation_complaint';
    case SuggestionFeedback = 'suggestion_feedback';
    case GeneralQuestion = 'general_question';
    case Other = 'other';

    public function getLabel(): string
    {
        return __('support::enums.category.' . $this->value);
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::LoginAccess => Heroicon::LockClosed,
            self::PlatformError => Heroicon::ExclamationTriangle,
            self::Bug => Heroicon::BugAnt,
            self::Integration => Heroicon::Link,
            self::Performance => Heroicon::Bolt,
            self::SchedulingIssue => Heroicon::CalendarDays,
            self::FinancialIssue => Heroicon::CreditCard,
            self::ContractPlan => Heroicon::DocumentText,
            self::CancellationComplaint => Heroicon::XCircle,
            self::SuggestionFeedback => Heroicon::LightBulb,
            self::GeneralQuestion => Heroicon::QuestionMarkCircle,
            self::Other => Heroicon::Squares2x2,
        };
    }

    public function getColor(): array|string
    {
        return match ($this) {
            self::LoginAccess,
            self::PlatformError,
            self::Bug,
            self::Integration,
            self::Performance,
            self::SchedulingIssue => Color::Blue,
            self::FinancialIssue => Color::Green,
            self::ContractPlan => Color::Amber,
            self::CancellationComplaint,
            self::GeneralQuestion,
            self::Other => Color::Purple,
            self::SuggestionFeedback => Color::Pink,
        };
    }

    /**
     * Destinations this category routes to. Single channel today; returning a list
     * lets a category fan out to several destinations later without touching the
     * orchestrator.
     *
     * @return array<TicketDestinationChannelEnum>
     */
    public function destinationChannels(): array
    {
        return [$this->getDestinationChannel()];
    }

    public function getDestinationChannel(): TicketDestinationChannelEnum
    {
        return match ($this) {
            self::LoginAccess,
            self::PlatformError,
            self::Bug,
            self::Integration,
            self::Performance,
            self::SchedulingIssue => TicketDestinationChannelEnum::SupportTi,
            self::FinancialIssue => TicketDestinationChannelEnum::Financial,
            self::ContractPlan => TicketDestinationChannelEnum::Commercial,
            self::CancellationComplaint => TicketDestinationChannelEnum::Cs,
            self::SuggestionFeedback => TicketDestinationChannelEnum::Product,
            self::GeneralQuestion,
            self::Other => TicketDestinationChannelEnum::Cs,
        };
    }
}
