<?php

namespace TresPontosTech\Billing\Core\Enums;

use App\Models\Users\User;
use BackedEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;
use TresPontosTech\Company\Models\Company;

enum BillableTypeEnum: string implements HasColor, HasIcon, HasLabel
{
    case User = User::class;

    case Company = Company::class;

    public function getColor(): array
    {
        return match ($this) {
            self::User => Color::Blue,
            self::Company => Color::Orange,
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::User => Heroicon::User,
            self::Company => Heroicon::BuildingOffice,
        };
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    public function isMetered(): bool
    {
        return $this === self::Company;
    }
}
