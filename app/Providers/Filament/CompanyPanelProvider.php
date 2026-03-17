<?php

namespace App\Providers\Filament;

use App\Filament\Shared\Pages\EditUserProfile;
use App\Filament\Shared\Pages\LoginPage;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
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
use TresPontosTech\Billing\Core\Pages\TenantSubscriptionPage;
use TresPontosTech\Billing\Stripe\Subscription\Company\CompanyBillingProvider;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Pages\Tenancy\EditTenantProfile;

class CompanyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('company')
            ->path('company')
            ->tenant(Company::class, slugAttribute: 'slug')
            ->login(LoginPage::class)
            ->profile(EditUserProfile::class)
            ->colors([
                'primary' => Color::hex('#F1785A'),
                ...Color::all(),
                'gray' => Color::Zinc,
            ])
            ->viteTheme('resources/css/filament/guest/theme.css')
            ->discoverResources(in: base_path('app-modules/panel-company/src/Filament/Resources'), for: 'TresPontosTech\\PanelCompany\\Filament\\Resources')
            ->discoverPages(in: base_path('app-modules/panel-company/src/Filament/Pages'), for: 'TresPontosTech\\PanelCompany\\Filament\\Pages')
            ->discoverWidgets(in: base_path('app-modules/panel-company/src/Filament/Widgets'), for: 'TresPontosTech\\PanelCompany\\Filament\\Widgets')
            ->discoverClusters(in: base_path('app-modules/panel-company/src/Filament/Clusters'), for: 'TresPontosTech\\PanelCompany\\Filament\\Clusters')
            ->pages([
                Dashboard::class,
                TenantSubscriptionPage::class,
            ])
            ->passwordReset()
            ->tenantProfile(EditTenantProfile::class)
            ->tenantMenuItems([
                'profile' => MenuItem::make()->hidden(),
                'billing' => MenuItem::make()->hidden(),
            ])
            ->navigationItems([
                NavigationItem::make(__('companies::resources.companies.company_settings'))
                    ->icon(Heroicon::Cog6Tooth)
                    ->group(__('all.settings'))
                    ->url(fn (): string => EditTenantProfile::getUrl()),
                NavigationItem::make(__('companies::resources.companies.billing_settings'))
                    ->icon(Heroicon::CreditCard)
                    ->group(__('all.settings'))
                    ->url(fn (): string => route('filament.company.tenant.billing', ['tenant' => Filament::getTenant()])),
                NavigationItem::make(__('all.my_profile'))
                    ->icon(Heroicon::UserCircle)
                    ->url(fn (): string => EditUserProfile::getUrl()),
            ])
            ->discoverWidgets(in: app_path('Filament/Company/Widgets'), for: 'App\\Filament\\Company\\Widgets')
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
            ->tenantBillingProvider(new CompanyBillingProvider)
            ->requiresTenantSubscription()
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
