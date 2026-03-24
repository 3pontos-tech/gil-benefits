<?php

namespace App\Providers\Filament;

use App\Filament\Shared\Pages\LoginPage;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use TresPontosTech\Consultants\Filament\Pages\ConsultantDashboard;
use TresPontosTech\Consultants\Filament\Pages\EditConsultantProfile;

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
            ->profile(EditConsultantProfile::class)
            ->pages([
                ConsultantDashboard::class,
            ])
            ->passwordReset()
            ->discoverResources(in: base_path('app-modules/panel-consultant/src/Filament/Resources'), for: 'TresPontosTech\\Consultants\\Filament\\Resources')
            ->discoverPages(in: base_path('app-modules/panel-consultant/src/Filament/Pages'), for: 'TresPontosTech\\Consultants\\Filament\\Pages')
            ->discoverWidgets(in: base_path('app-modules/panel-consultant/src/Filament/Widgets'), for: 'TresPontosTech\\Consultants\\Filament\\Widgets')
            ->discoverClusters(in: base_path('app-modules/panel-consultant/src/Filament/Clusters'), for: 'TresPontosTech\\Consultants\\Filament\\Clusters')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->navigationItems([
                NavigationItem::make(__('all.my_profile'))
                    ->sort(5)
                    ->icon(Heroicon::UserCircle)
                    ->url(fn (): string => EditConsultantProfile::getUrl()),
            ])
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
