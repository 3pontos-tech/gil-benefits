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
use TresPontosTech\Billing\Core\Pages\SubscriptionPage;
use TresPontosTech\Billing\Stripe\Subscription\Company\CompanyBillingProvider;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\Tenant\Filament\Pages\Tenancy\EditCompany;
use TresPontosTech\Tenant\Filament\Pages\Tenancy\RegisterCompany;
use TresPontosTech\Tenant\Filament\Widgets\TenantPlanStatusStats;

class CompanyPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('company')
            ->tenant(Company::class, slugAttribute: 'slug')
            ->path('company')
            ->login(LoginPage::class)
            ->colors([
                'primary' => Color::hex('#F1785A'),
                ...Color::all(),
                'gray' => Color::Zinc,
            ])
            ->viteTheme('resources/css/filament/guest/theme.css')
            ->discoverResources(in: app_path('Filament/Company/Resources'), for: 'App\\Filament\\Company\\Resources')
            ->discoverPages(in: app_path('Filament/Company/Pages'), for: 'App\\Filament\\Company\\Pages')
            ->pages([
                Dashboard::class,
                SubscriptionPage::class,
            ])
            ->tenant(Company::class)
            ->tenantRegistration(RegisterCompany::class)
            ->tenantProfile(EditCompany::class)
            ->registration()
            ->discoverWidgets(in: app_path('Filament/Company/Widgets'), for: 'App\\Filament\\Company\\Widgets')
            ->widgets([
                TenantPlanStatusStats::class,
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
            ->tenantBillingProvider(new CompanyBillingProvider)
            ->requiresTenantSubscription()

            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
