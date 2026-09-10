<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CompanyPlanKindEnum: string implements HasColor, HasIcon, HasLabel
{
    case MonthlyQuota = 'monthly_quota';
    case CreditsOnly = 'credits_only';

    public function getColor(): array
    {
        return match ($this) {
            self::MonthlyQuota => Color::Indigo,
            self::CreditsOnly => Color::Amber,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::MonthlyQuota => 'heroicon-o-arrow-path',
            self::CreditsOnly => 'heroicon-o-ticket',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::MonthlyQuota => 'Cota mensal',
            self::CreditsOnly => 'Somente crédito',
        };
    }

    public function grantsMonthlyQuota(): bool
    {
        return $this === self::MonthlyQuota;
    }
}
