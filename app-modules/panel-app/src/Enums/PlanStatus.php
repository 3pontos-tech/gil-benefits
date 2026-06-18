<?php

declare(strict_types=1);

namespace TresPontosTech\App\Enums;

use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Support\Htmlable;

enum PlanStatus: string implements HasLabel
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Expired = 'expired';

    public function getLabel(): string|Htmlable|null
    {
        return match ($this) {
            self::Active => __('panel-app::widgets.plan_status.active'),
            self::Inactive => __('panel-app::widgets.plan_status.inactive'),
            self::Expired => __('panel-app::widgets.plan_status.expired'),
        };
    }
}
