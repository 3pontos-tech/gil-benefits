<?php

namespace App\Providers\Filament;

use App\Filament\Shared\Pages\LoginPage;
use Filament\Actions\Action;
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
use Filament\Tables\Table;
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
                // Laranja da marca (#E2410A, o orange-primary do tema guest).
                //
                // Régua manual pelo mesmo motivo da paleta vermelha anterior:
                // Color::hex() só aproveita o matiz e não devolveria o tom exato.
                // Esta régua copia L/C da Color::Orange no matiz da marca, mas o
                // hex é pinado no 600 — não no 500, como no vermelho — porque a
                // luminosidade do #E2410A (0.608) é de um 600; no 500 a régua
                // sairia da ordem. O 600 também é o tom dos botões no tema claro,
                // então a cor da marca aparece literal onde mais se vê.
                'primary' => [
                    50 => 'oklch(0.980 0.016 35.769)',
                    100 => 'oklch(0.954 0.038 35.769)',
                    200 => 'oklch(0.901 0.076 35.769)',
                    300 => 'oklch(0.837 0.128 35.769)',
                    400 => 'oklch(0.750 0.183 35.769)',
                    500 => 'oklch(0.705 0.213 35.769)',
                    600 => 'oklch(0.608 0.205 35.769)',
                    700 => 'oklch(0.553 0.195 35.769)',
                    800 => 'oklch(0.470 0.157 35.769)',
                    900 => 'oklch(0.408 0.123 35.769)',
                    950 => 'oklch(0.266 0.079 35.769)',
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
            ->breadcrumbs(false)
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
            // Padrão de toda tabela do painel, casando com o CSS do tema que
            // já estiliza .fi-ta sem escopo por resource: paginação de 6 com
            // saltos extremos e os rótulos de busca/filtro do layout. Fica no
            // bootUsing para valer só quando este painel atende a request;
            // cada resource segue livre para sobrescrever o que precisar.
            ->bootUsing(function (): void {
                Table::configureUsing(function (Table $table): void {
                    $table
                        ->searchPlaceholder(__('all.tables.search_placeholder'))
                        ->filtersTriggerAction(fn (Action $action): Action => $action
                            ->button()
                            ->outlined()
                            ->color('gray')
                            ->label(function (Table $table): string {
                                $label = __('all.tables.filters_label');
                                $count = $table->getActiveFiltersCount();

                                return $count > 0 ? sprintf('%s (%d)', $label, $count) : $label;
                            }))
                        ->paginationPageOptions([6, 12, 24])
                        ->defaultPaginationPageOption(6)
                        ->extremePaginationLinks();
                });
            })
            ->viteTheme('resources/css/filament/app/theme.css');
    }
}
