<?php

declare(strict_types=1);

namespace TresPontosTech\Company\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum DepartmentCategory: string implements HasColor, HasIcon, HasLabel
{
    case HumanResources = 'human_resources';
    case Technology = 'technology';
    case Finance = 'finance';
    case Commercial = 'commercial';
    case Marketing = 'marketing';
    case Operations = 'operations';
    case Legal = 'legal';
    case Administrative = 'administrative';
    case Executive = 'executive';
    case CustomerSuccess = 'customer_success';

    public function getLabel(): string
    {
        return __('companies::enums.department_category.' . $this->value);
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::HumanResources => 'heroicon-o-users',
            self::Technology => 'heroicon-o-computer-desktop',
            self::Finance => 'heroicon-o-currency-dollar',
            self::Commercial => 'heroicon-o-shopping-bag',
            self::Marketing => 'heroicon-o-megaphone',
            self::Operations => 'heroicon-o-cog-6-tooth',
            self::Legal => 'heroicon-o-scale',
            self::Administrative => 'heroicon-o-building-office',
            self::Executive => 'heroicon-o-star',
            self::CustomerSuccess => 'heroicon-o-chat-bubble-left-right',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::HumanResources => 'info',
            self::Technology => 'primary',
            self::Finance => 'success',
            self::Commercial => 'warning',
            self::Marketing => 'danger',
            self::Operations => 'gray',
            self::Legal => 'warning',
            self::Administrative => 'gray',
            self::Executive => 'primary',
            self::CustomerSuccess => 'info',
        };
    }
}
