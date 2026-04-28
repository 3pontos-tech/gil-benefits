<?php

namespace TresPontosTech\Billing\Core\Enums;

use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;
use TresPontosTech\Billing\Barte\BarteAdapter;
use TresPontosTech\Billing\Stripe\Subscription\StripeAdapter;

enum BillingProviderEnum: string implements HasColor, HasIcon, HasLabel
{
    case Stripe = 'stripe';
    case Contractual = 'contractual';
    case Barte = 'barte';

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Stripe => Color::Indigo,
            self::Contractual => Color::Emerald,
            self::Barte => Color::Olive,
        };
    }

    public function getIcon(): string|BackedEnum|null
    {
        return match ($this) {
            self::Stripe, self::Barte => 'heroicon-o-credit-card',
            self::Contractual => 'heroicon-o-document-text',
        };
    }

    public function getLabel(): string|Htmlable|null
    {
        return $this->name;
    }

    public static function availableProviders(): array
    {
        return [
            'barte' => BarteAdapter::class,
            'stripe' => StripeAdapter::class,
        ];
    }

    public static function activeCases(): array
    {
        return array_map(
            fn (string $key) => self::from($key),
            array_keys(self::availableProviders())
        );
    }
}
