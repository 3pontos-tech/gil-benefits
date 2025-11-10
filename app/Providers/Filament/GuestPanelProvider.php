<?php

namespace App\Providers\Filament;

use App\Filament\Guest\Pages\LandingPage;
use Filament\Actions\Action;
use Filament\Enums\ThemeMode;
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
            ->renderHook(PanelsRenderHook::FOOTER, fn (): Factory|View => view('components.guest-footer'))
            ->renderHook(PanelsRenderHook::TOPBAR_END, fn () => Blade::render(<<<'BLADE'
               @guest
                    <x-button class="w-fit!" variant="outline" tag='a' href='/app/login'>Acessar Plataforma</x-button>
               @endguest
            BLADE
            ))
            ->viteTheme('resources/css/filament/guest/theme.css')
            ->topNavigation()
            ->discoverResources(in: app_path('Filament/Guest/Resources'), for: 'App\Filament\Guest\Resources')
            ->discoverPages(in: app_path('Filament/Guest/Pages'), for: 'App\Filament\Guest\Pages')
            ->pages([
                LandingPage::class,
            ])
            ->userMenuItems([
                Action::make('user_panel')
                    ->label('Acessar Plataforma')
                    ->url('/app')
                    ->icon('heroicon-o-user-group')
                    ->visible(fn () => auth()->check()),
                Action::make('company_panel')
                    ->label('Administrativo da Empresa')
                    ->url('/company')
                    ->icon(Heroicon::BuildingOffice)
                    ->visible(fn (): bool => auth()->check() && auth()->user()->ownedCompanies()->exists()),
            ])
            ->navigationItems([
                NavigationItem::make('Inicio')
                    ->url('#home')
                    ->sort(0),
                NavigationItem::make('Como Funciona')
                    ->url('#how-it-works')
                    ->sort(2),
                NavigationItem::make('Nosso Desafio')
                    ->url('#challenge')
                    ->sort(3),
                NavigationItem::make('Consultoria')
                    ->url('#assessment')
                    ->sort(4),
                NavigationItem::make('Preços')
                    ->url('#pricing')
                    ->sort(5),
                NavigationItem::make('FAQ')
                    ->url('#faq')
                    ->sort(6),
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
