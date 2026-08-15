<?php

namespace TresPontosTech\Billing\Core\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum BillingProviderEnum: string implements HasColor, HasIcon, HasLabel
{
    case Stripe = 'stripe';
    case Contractual = 'contractual';
    case Barte = 'barte';
    case Virtu = 'virtu';

    public function getColor(): array
    {
        return match ($this) {
            self::Stripe => Color::Indigo,
            self::Contractual => Color::Emerald,
            self::Barte => Color::Olive,
            self::Virtu => Color::Cyan,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Stripe, self::Barte, self::Virtu => 'heroicon-o-credit-card',
            self::Contractual => 'heroicon-o-document-text',
        };
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    /**
     * Providers whose existing subscriptions are considered valid for access.
     * Includes legacy providers while their plans have not yet expired.
     *
     * @return list<self>
     */
    public static function activeCases(): array
    {
        return [self::Stripe, self::Barte, self::Virtu];
    }

    /**
     * Available Providers for NEW Subscriptions.
     *
     * Virtu only, and Barte deliberately dropped: TenantSubscriptionPage picks
     * `checkoutCases()[0]`, so a second entry would never be reached for company
     * checkout while still duplicating plans on the user page, which lists every
     * provider in this array. Barte stays in activeCases(), so subscriptions
     * already sold through it keep granting access.
     *
     * Selling through both at once needs a per-tenant choice replacing that
     * `[0]` — not another element here.
     *
     * @return list<self>
     */
    public static function checkoutCases(): array
    {
        return [self::Virtu];
    }
}
