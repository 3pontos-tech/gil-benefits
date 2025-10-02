<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PlanTypeEnum: string implements HasColor, HasLabel
{
    case Monthly = 'monthly';

    case SemiAnnual = 'semi-annual';
    case Annual = 'annual';

    public function getColor(): string
    {
        return match ($this) {
            self::Monthly => 'success',
            self::SemiAnnual => 'warning',
            self::Annual => 'info',
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Monthly => 'Monthly',
            self::SemiAnnual => 'Semi-Annual',
            self::Annual => 'Annual',
        };
    }
}
