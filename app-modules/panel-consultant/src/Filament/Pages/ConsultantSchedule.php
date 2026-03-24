<?php

namespace TresPontosTech\Consultants\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ConsultantSchedule extends Page
{
    protected string $view = 'consultants::filament.pages.consultant-schedule';

    protected static ?string $navigationLabel = 'Consultant Schedule';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;
}
