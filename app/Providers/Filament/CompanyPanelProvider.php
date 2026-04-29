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
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use TresPontosTech\Billing\Core\Pages\BillingManagePage;
use TresPontosTech\Billing\Core\Pages\TenantSubscriptionPage;
use TresPontosTech\Billing\Stripe\Subscription\Company\CompanyBillingProvider;
use TresPontosTech\Company\Models\Company;
use TresPontosTech\PanelCompany\Filament\Pages\Tenancy\EditTenantProfile;
use TresPontosTech\PanelCompany\Filament\Pages\Tenancy\RegisterTenant;

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
                BillingManagePage::class,
            ])
            ->passwordReset()
            ->registration()
            ->tenantRegistration(RegisterTenant::class)
            ->tenantProfile(EditTenantProfile::class)
            ->brandLogo(function (): ?HtmlString {
                /** @var Company|null $company */
                $company = filament()->getTenant();

                if ($company === null) {
                    return null;
                }

                $media = $company->getFirstMedia('company_logo');

                if (! $media) {
                    return null;
                }

                $signedUrl = $media->getTemporaryUrl(
                    now()->addMinutes(60),
                );

                return new HtmlString("
                <img src='{$signedUrl}'
                     alt='Logo'
                     style='height: 3.5rem; width: auto; object-fit: contain;'
                     class='fi-logo'>
        ");
            })
            ->brandLogoHeight('3rem')
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
                    ->visible(function (): bool {
                        /** @var Company|null $tenant */
                        $tenant = filament()->getTenant();

                        return ! $tenant?->hasActivePlan();
                    })
                    ->url(fn (): string => route('filament.company.tenant.billing', ['tenant' => Filament::getTenant()])),
                NavigationItem::make(__('all.my_profile'))
                    ->icon(Heroicon::UserCircle)
                    ->url(fn (): string => EditUserProfile::getUrl()),
            ])
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): Factory|View => view('filament.shared.import-errors-modal'),
            )
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
