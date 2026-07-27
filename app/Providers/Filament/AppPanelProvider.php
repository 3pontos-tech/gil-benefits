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
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use TresPontosTech\Billing\Stripe\Subscription\User\UserBillingProvider;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelApp\Filament\Pages\EditUserProfile;
use TresPontosTech\PanelApp\Filament\Pages\UserRegistration;
use TresPontosTech\PanelApp\Http\Middleware\RedirectIfAnamneseNotCompleted;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login(LoginPage::class)
            ->profile(EditUserProfile::class)
            ->colors([
                'primary' => Color::hex('#F1785A'),
            ])
            ->registration(UserRegistration::class)
            ->passwordReset()
            // Sem isso o layout do Filament cai no fallback de 7xl (80rem) e sobra
            // uma faixa vazia entre a sidebar e o conteúdo nas telas largas.
            ->maxContentWidth(Width::Full)
            ->sidebarFullyCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->globalSearch(false)
            ->navigationItems([
                NavigationItem::make(__('all.my_profile'))
                    ->icon(Heroicon::UserCircle)
                    ->url(fn (): string => EditUserProfile::getUrl()),
                NavigationItem::make(__('all.my_subscription'))
                    ->icon(Heroicon::CreditCard)
                    ->group(__('all.billing'))
                    ->visible(function (): bool {
                        /** @var Company|null $tenant */
                        $tenant = filament()->getTenant();

                        return ! $tenant?->hasActivePlan();
                    })
                    ->url(fn (): string => route('filament.app.tenant.billing', ['tenant' => Filament::getTenant()])),
            ])
            ->discoverResources(in: base_path('app-modules/panel-app/src/Filament/Resources'), for: 'TresPontosTech\\PanelApp\\Filament\\Resources')
            ->discoverPages(in: base_path('app-modules/panel-app/src/Filament/Pages'), for: 'TresPontosTech\\PanelApp\\Filament\\Pages')
            ->discoverWidgets(in: base_path('app-modules/panel-app/src/Filament/Widgets'), for: 'TresPontosTech\\PanelApp\\Filament\\Widgets')
            ->discoverClusters(in: base_path('app-modules/panel-app/src/Filament/Clusters'), for: 'TresPontosTech\\PanelApp\\Filament\\Clusters')
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
            ->tenant(Company::class, slugAttribute: 'slug')
            ->requiresTenantSubscription()
            ->tenantMiddleware([
                RedirectIfAnamneseNotCompleted::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->viteTheme('resources/css/filament/app/theme.css');
    }
}
