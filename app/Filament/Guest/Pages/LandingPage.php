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

    /**
     * Largura cheia, como na CompaniesPage: cada seção da home carrega o próprio
     * `max-w-[1800px]` e as faixas do hero e do "estresse financeiro" sangram de ponta
     * a ponta com `left-1/2 -mx-[50vw] w-screen`. Com o container limitado aqui, o
     * `left-1/2` deslocava metade da largura do container em vez da viewport, e o hero
     * saía torto e cortado à esquerda.
     */
    protected Width|string|null $maxContentWidth = Width::Full;

    public function getHeading(): string
    {
        return '';
    }

    public function getSubheading(): ?string
    {
        return null;
    }
}
