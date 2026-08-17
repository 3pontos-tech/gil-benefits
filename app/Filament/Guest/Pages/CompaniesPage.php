<?php

declare(strict_types=1);

namespace App\Filament\Guest\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class CompaniesPage extends Page
{
    protected static ?string $slug = 'para-empresas';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Para Empresas';

    protected string $view = 'filament.guest.pages.companies';

    /**
     * O design compõe em 1920px com conteúdo de 1676px (gutter de 122px de cada lado).
     * Largura cheia aqui + `max-w-[1676px]` na view reproduz exatamente esse gutter em 1920px,
     * e permite que as faixas decorativas sangrem de ponta a ponta.
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
