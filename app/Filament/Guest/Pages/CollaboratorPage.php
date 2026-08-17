<?php

declare(strict_types=1);

namespace App\Filament\Guest\Pages;

use Filament\Pages\Page;
use Filament\Support\Enums\Width;

class CollaboratorPage extends Page
{
    protected static ?string $slug = 'colaborador';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Para Colaboradores';

    protected string $view = 'filament.guest.pages.collaborator';

    /**
     * Mesma razão da CompaniesPage: o design compõe em 1920px com conteúdo de 1676px
     * (gutter de 122px de cada lado). Largura cheia aqui + `max-w-[1800px]` na view
     * reproduz esse gutter, e permite que o hero e as massas decorativas sangrem de
     * ponta a ponta com `left-1/2 -mx-[50vw] w-screen`.
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
