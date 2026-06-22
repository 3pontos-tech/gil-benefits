<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserCreditStatusEnum: string implements HasColor, HasLabel
{
    case Available = 'available';
    case InUse = 'in_use';
    case Used = 'used';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Available => __('billing::enums.user_credit_status.available'),
            self::InUse => __('billing::enums.user_credit_status.in_use'),
            self::Used => __('billing::enums.user_credit_status.used'),
            self::Expired => __('billing::enums.user_credit_status.expired'),
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Available => Color::Emerald,
            self::InUse => Color::Blue,
            self::Used => Color::Gray,
            self::Expired => Color::Orange,
        };
    }
}
