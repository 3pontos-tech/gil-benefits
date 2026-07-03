<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Enums;

use Filament\Support\Contracts\HasLabel;

enum PlanStatus: string implements HasLabel
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Expired = 'expired';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => __('panel-app::widgets.plan_status.active'),
            self::Inactive => __('panel-app::widgets.plan_status.inactive'),
            self::Expired => __('panel-app::widgets.plan_status.expired'),
        };
    }
}
