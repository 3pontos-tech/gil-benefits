<?php

namespace App\Filament\Guest\Pages;

use Filament\Pages\Dashboard;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class LandingPage extends Dashboard
{
    protected string $view = 'filament.guest.pages.landing-page';

    protected static ?string $navigationLabel = 'Home';

    protected static bool $shouldRegisterNavigation = false;

    protected Width | string | null $maxContentWidth = Width::ScreenExtraLarge;

}
