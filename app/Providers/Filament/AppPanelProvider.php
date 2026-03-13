<?php

namespace App\Providers\Filament;

use App\Filament\Shared\Pages\LoginPage;
use Filament\Facades\Filament;
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
use TresPontosTech\Billing\Stripe\Subscription\User\UserBillingProvider;
use TresPontosTech\Company\Models\Company;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login(LoginPage::class)
            ->colors([
                'primary' => Color::hex('#F1785A'),
            ])
            ->passwordReset()
            ->topbar(false)
            ->sidebarFullyCollapsibleOnDesktop()
            ->globalSearch()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->discoverResources(in: base_path('app-modules/panel-app/src/Filament/Resources'), for: 'TresPontosTech\\App\\Filament\\Resources')
            ->discoverPages(in: base_path('app-modules/panel-app/src/Filament/Pages'), for: 'TresPontosTech\\App\\Filament\\Pages')
            ->discoverWidgets(in: base_path('app-modules/panel-app/src/Filament/Widgets'), for: 'TresPontosTech\\App\\Filament\\Widgets')
            ->discoverClusters(in: base_path('app-modules/panel-app/src/Filament/Clusters'), for: 'TresPontosTech\\App\\Filament\\Clusters')
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
            ->searchableTenantMenu(false)
            ->tenantMenu(false)
            ->tenantBillingProvider(new UserBillingProvider)

            ->navigationItems([
                NavigationItem::make('Minha Assinatura')
                    ->icon(Heroicon::CreditCard)
                    ->url(fn (): string => route('filament.app.tenant.billing', ['tenant' => Filament::getTenant()])),
            ])
            ->tenant(Company::class, slugAttribute: 'slug')
            ->requiresTenantSubscription()
            ->authMiddleware([
                Authenticate::class,
            ])
            ->viteTheme('resources/css/filament/app/theme.css');
    }
}
