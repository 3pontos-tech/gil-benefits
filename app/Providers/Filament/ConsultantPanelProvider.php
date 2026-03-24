<?php

namespace App\Providers\Filament;

use App\Filament\Shared\Pages\LoginPage;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ConsultantPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('consultant')
            ->path('consultant')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->login(LoginPage::class)
            ->pages([
                Dashboard::class,
            ])
            ->passwordReset()
            ->discoverResources(in: base_path('app-modules/panel-consultant/src/Filament/Resources'), for: 'TresPontosTech\\Consultants\\Filament\\Resources')
            ->discoverPages(in: base_path('app-modules/panel-consultant/src/Filament/Pages'), for: 'TresPontosTech\\Consultants\\Filament\\Pages')
            ->discoverWidgets(in: base_path('app-modules/panel-consultant/src/Filament/Widgets'), for: 'TresPontosTech\\Consultants\\Filament\\Widgets')
            ->discoverClusters(in: base_path('app-modules/panel-consultant/src/Filament/Clusters'), for: 'TresPontosTech\\Consultants\\Filament\\Clusters')
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
