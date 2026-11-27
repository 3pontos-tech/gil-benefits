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
     * Providers whose plans count when checking access.
     *
     * @return list<self>
     */
    public static function activeCases(): array
    {
        return [self::Stripe];
    }

    /**
     * Gateways whose code was removed: their rows stay as history and never grant access
     * again, since no webhook will ever close them. See ADR billing
     * `2026-11-27-retired-gateways-keep-their-data`.
     *
     * @return list<self>
     */
    public static function retiredCases(): array
    {
        return [self::Barte, self::Virtu];
    }

    /**
     * Providers available for NEW subscriptions.
     *
     * TenantSubscriptionPage picks `checkoutCases()[0]`, so a second entry would never be
     * reached for company checkout while still duplicating plans on the user page, which
     * lists every provider in this array. Selling through two gateways at once needs a
     * per-tenant choice replacing that `[0]`, not another element here.
     *
     * @return list<self>
     */
    public static function checkoutCases(): array
    {
        return [self::Stripe];
    }
}
