<?php

namespace TresPontosTech\User\Filament\App\Widgets;

use Filament\Widgets\AccountWidget;

class UserAccountWidget  extends AccountWidget {
    protected int | string | array $columnSpan = 2;

    protected static ?int $sort = 0;
}
