<?php

namespace App\Providers;

use App\Filament\FilamentPanel;
use App\Http\Controllers\RedirectToTenantOrShowNoCompany;
use App\Http\Middleware\AuthenticatePanel;
use App\Http\Middleware\IdentifyTenantOrRedirectHome;
use Filament\Http\Controllers\RedirectToTenantController;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\IdentifyTenant;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->configureMacros();
        $this->overridePanelAccessMiddleware();
    }

    public function configureMacros(): void
    {
        Panel::macro('currentPanel', fn (): FilamentPanel => FilamentPanel::from($this->getId()));
    }

    /**
     * Swap Filament's access-control handlers for versions that guide an
     * authenticated user to a useful place instead of showing a raw error:
     * a 403 (wrong panel) and a 404 (company they are not part of) become a
     * redirect to their profile home, and a collaborator with no active company
     * sees a friendly page instead of a 404 on the app panel root. Binding in the
     * container covers HTTP routes, Livewire persistent middleware and the
     * tenant-root controller.
     */
    public function overridePanelAccessMiddleware(): void
    {
        $this->app->bind(Authenticate::class, AuthenticatePanel::class);
        $this->app->bind(IdentifyTenant::class, IdentifyTenantOrRedirectHome::class);
        $this->app->bind(RedirectToTenantController::class, RedirectToTenantOrShowNoCompany::class);
    }
}
