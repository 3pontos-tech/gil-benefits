<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CreditOrderStatusEnum: string implements HasColor, HasLabel
{
    case Pending = 'pending';

    case Paid = 'paid';

    public function getColor(): array
    {
        return match ($this) {
            self::Pending => Color::Amber,
            self::Paid => Color::Green,
        };
    }

    public function getLabel(): string
    {
        return __('credits::enums.credit_order_status.' . $this->value);
    }
}
