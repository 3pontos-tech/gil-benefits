<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PlanTypeEnum: string implements HasColor, HasLabel
{
    case Monthly = 'monthly';
    case Annual = 'annual';

    public function getColor(): string
    {
        return match ($this) {
            self::Monthly => 'success',
            self::Annual => 'info',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::Annual => 'Annual',
        };
    }
}
