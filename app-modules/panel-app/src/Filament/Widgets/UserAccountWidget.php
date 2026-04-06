<?php

declare(strict_types=1);

namespace TresPontosTech\App\Filament\Widgets;

use Filament\Widgets\AccountWidget;

class UserAccountWidget extends AccountWidget
{
    protected int|string|array $columnSpan = 2;

    protected static ?int $sort = 0;
}
