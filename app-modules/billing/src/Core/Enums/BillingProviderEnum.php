<?php

namespace TresPontosTech\Billing\Core\Enums;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum BillingProviderEnum: string implements HasColor, HasIcon, HasLabel
{
    case Stripe = 'stripe';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Stripe => Color::Indigo,
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Stripe => 'heroicon-o-credit-card',
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }
}
