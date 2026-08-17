<?php

namespace App\Providers\Filament;

use App\Filament\Guest\Pages\CollaboratorPage;
use App\Filament\Guest\Pages\CompaniesPage;
use App\Filament\Guest\Pages\HelpCenterPage;
use App\Filament\Guest\Pages\LandingPage;
use Filament\Actions\Action;
use Filament\Enums\ThemeMode;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class GuestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('guest')
            ->path('')
            ->defaultThemeMode(ThemeMode::Light)
            ->colors([
                'primary' => Color::hex('FD0342'),
            ])
            ->brandLogo(fn (): Factory|View => view('components.logo', ['color' => 'dark']))
            ->darkModeBrandLogo(fn (): Factory|View => view('components.logo', ['color' => 'white']))
            ->brandName('Flamma')
            // O padrão do Filament é Inter Variable, buscada no Google Fonts. O design é
            // todo Space Grotesk, que o theme.css já declara em @font-face — o LocalFontProvider
            // sem url só ajusta --font-family e não emite <link> nenhum.
            ->font('Space Grotesk', provider: LocalFontProvider::class)
            ->renderHook(PanelsRenderHook::FOOTER, fn (): Factory|View => view('components.guest-footer'))
            // Camada de movimento compartilhada pelas três páginas do site. Fica num
            // bundle próprio porque o painel guest não carrega resources/js/app.js — ele só
            // monta o layout do Filament mais o viteTheme abaixo.
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => Blade::render("@vite('resources/js/flamma-motion.js')"),
                scopes: [LandingPage::class, CompaniesPage::class, CollaboratorPage::class],
            )
            ->renderHook(PanelsRenderHook::TOPBAR_END, fn () => Blade::render(<<<'BLADE'
               @guest
                    <x-button class="w-fit!" variant="outline" tag='a' href='/app/login'>Acesso Colaborador</x-button>
               @endguest
            BLADE
            ))
            ->viteTheme('resources/css/filament/guest/theme.css')
            ->topNavigation()
            ->discoverResources(in: app_path('Filament/Guest/Resources'), for: 'App\Filament\Guest\Resources')
            ->discoverPages(in: app_path('Filament/Guest/Pages'), for: 'App\Filament\Guest\Pages')
            ->pages([
                LandingPage::class,
                CompaniesPage::class,
                CollaboratorPage::class,
            ])
            ->userMenuItems([
                Action::make('user_panel')
                    ->label('Acessar Plataforma')
                    ->url('/app')
                    ->icon('heroicon-o-user-group')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'admin', 'employee'])),

                Action::make('admin_panel')
                    ->label('Painel Administrativo')
                    ->url('/admin')
                    ->icon('heroicon-o-shield-check')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'admin'])),

                Action::make('company_panel')
                    ->label('Administrativo da Empresa')
                    ->url('/company')
                    ->icon(Heroicon::BuildingOffice)
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'admin', 'company_owner'])),
            ])
            ->navigationItems([
                NavigationItem::make('Inicio')
                    ->url(fn (): string => LandingPage::getUrl() . '#home')
                    ->sort(0),
                NavigationItem::make('Para Empresas')
                    ->url(fn (): string => CompaniesPage::getUrl())
                    ->isActiveWhen(fn (): bool => request()->routeIs(CompaniesPage::getRouteName()))
                    ->sort(1),
                NavigationItem::make('Para Você')
                    ->url(fn (): string => CollaboratorPage::getUrl())
                    ->isActiveWhen(fn (): bool => request()->routeIs(CollaboratorPage::getRouteName()))
                    ->sort(2),
                NavigationItem::make('Como Funciona')
                    ->url(fn (): string => LandingPage::getUrl() . '#how-it-works')
                    ->sort(3),
                NavigationItem::make('Nosso Desafio')
                    ->url(fn (): string => LandingPage::getUrl() . '#challenge')
                    ->sort(4),
                // A home reconstruída não tem mais a âncora #assessment; a seção equivalente
                // passou a ser "Por que confiar no Flamma?".
                NavigationItem::make('Por que confiar')
                    ->url(fn (): string => LandingPage::getUrl() . '#por-que-confiar')
                    ->sort(5),
                NavigationItem::make('Preços')
                    ->url(fn (): string => LandingPage::getUrl() . '#pricing')
                    ->sort(6),
                NavigationItem::make('FAQ')
                    ->url(fn (): string => LandingPage::getUrl() . '#faq')
                    ->sort(7),
                NavigationItem::make('Abrir Chamado')
                    ->group('Ajuda')
                    ->icon(Heroicon::QuestionMarkCircle)
                    ->url(fn (): string => HelpCenterPage::getUrl())
                    ->sort(8),
            ])
            ->discoverWidgets(in: app_path('Filament/Guest/Widgets'), for: 'App\Filament\Guest\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
            ]);
    }
}
