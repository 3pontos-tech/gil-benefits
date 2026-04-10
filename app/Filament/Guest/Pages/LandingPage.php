<?php

declare(strict_types=1);

namespace App\Filament\Guest\Pages;

use Filament\Pages\Dashboard;
use Filament\Support\Enums\Width;

class LandingPage extends Dashboard
{
    protected string $view = 'filament.guest.pages.landing-page';

    protected static ?string $navigationLabel = 'Home';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Clube de Benefícios';

    protected Width|string|null $maxContentWidth = Width::ScreenTwoExtraLarge;

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }
}
