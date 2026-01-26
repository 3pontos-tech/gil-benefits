<?php

namespace TresPontosTech\User\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Support\ServiceProvider;

class UserServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'app') {
                $panel->discoverResourcesForPanel('user', FilamentPanel::User);
            }
        });
    }
}
