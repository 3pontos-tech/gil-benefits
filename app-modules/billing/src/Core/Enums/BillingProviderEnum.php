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
    case Contractual = 'contractual';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Stripe => Color::Indigo,
            self::Contractual => Color::Emerald,
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Stripe => 'heroicon-o-credit-card',
            self::Contractual => 'heroicon-o-document-text',
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }
}
