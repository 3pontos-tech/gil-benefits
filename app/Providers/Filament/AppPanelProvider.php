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
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View as ViewContract;
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
                // Vermelho forte da marca (#FD0342, o --brand-primary do tema
                // guest e o início do gradiente do cartão de créditos).
                //
                // A paleta é manual porque Color::hex() só aproveita o matiz do
                // hex — luminosidade e croma vêm de uma régua fixa que satura em
                // ~0.17, então o #FD0342 (croma 0.252) nunca apareceria e os
                // botões reprovariam no contraste AA, caindo em fundo claro com
                // texto escuro. Esta régua copia L/C da Color::Red (cujo 600
                // passa AA com texto branco) no matiz da marca, com o 500
                // pinado no #FD0342 exato.
                'primary' => [
                    50 => 'oklch(0.971 0.013 20.328)',
                    100 => 'oklch(0.936 0.032 20.328)',
                    200 => 'oklch(0.885 0.062 20.328)',
                    300 => 'oklch(0.808 0.114 20.328)',
                    400 => 'oklch(0.704 0.191 20.328)',
                    500 => 'oklch(0.629 0.252 20.328)',
                    600 => 'oklch(0.577 0.245 20.328)',
                    700 => 'oklch(0.505 0.213 20.328)',
                    800 => 'oklch(0.444 0.177 20.328)',
                    900 => 'oklch(0.396 0.141 20.328)',
                    950 => 'oklch(0.258 0.092 20.328)',
                ],
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
            // Alternador claro/escuro na topbar, entre o sino e o avatar.
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): ViewContract => view('filament.app.topbar.theme-toggle'),
            )
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
