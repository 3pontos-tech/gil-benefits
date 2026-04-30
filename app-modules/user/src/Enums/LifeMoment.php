<?php

namespace TresPontosTech\User\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

enum LifeMoment: string implements HasDescription, HasIcon, HasLabel
{
    case Endebted = 'endebted';

    case Payer = 'payer';

    case Messy = 'messy';

    case Saver = 'saver';

    case Investor = 'investor';

    public function getLabel(): string|Htmlable|null
    {
        return __('user::enums.life_moment.' . $this->value . '.label');
    }

    public function getDescription(): string|Htmlable|null
    {
        return __('user::enums.life_moment.' . $this->value . '.description');
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Endebted => Heroicon::ExclamationTriangle,
            self::Payer => Heroicon::CreditCard,
            self::Messy => Heroicon::ArrowsRightLeft,
            self::Saver => Heroicon::Banknotes,
            self::Investor => Heroicon::ArrowTrendingUp,
        };
    }
}
