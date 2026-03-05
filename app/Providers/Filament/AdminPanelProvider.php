<?php

namespace App\Providers\Filament;

use App\Filament\Shared\Pages\LoginPage;
use Basement\BetterMails\Filament\FilamentBetterEmailPlugin;
use Basement\Webhooks\FilamentWebhookPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(LoginPage::class)
            ->colors([
                'primary' => Color::Blue,
                'zinc' => Color::Zinc,
                'slate' => Color::Slate,
            ])
            ->discoverClusters(in: base_path('app-modules/panel-admin/src/Filament/Clusters'), for: 'TresPontosTech\\Admin\\Filament\\Clusters')
            ->discoverPages(in: base_path('app-modules/panel-admin/src/Filament/Pages'), for: 'TresPontosTech\\Admin\\Filament\\Pages')
            ->discoverResources(in: base_path('app-modules/panel-admin/src/Filament/Resources'), for: 'TresPontosTech\\Admin\\Filament\\Resources')
            ->discoverWidgets(in: base_path('app-modules/panel-admin/src/Filament/Widgets'), for: 'TresPontosTech\\Admin\\Filament\\Widgets')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->plugins([
                FilamentWebhookPlugin::make(),
                FilamentBetterEmailPlugin::make(),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
