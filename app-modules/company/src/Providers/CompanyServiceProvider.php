<?php

namespace TresPontosTech\Company\Providers;

use App\Filament\FilamentPanel;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\Company\Models\Company;

class CompanyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Relation::morphMap([
            'company' => Company::class,
        ]);
    }

    public function boot(): void
    {
        Panel::configureUsing(function (Panel $panel): void {
            if ($panel->getId() === 'admin') {
                $panel->discoverResourcesForPanel('company', FilamentPanel::Admin);
            }
        });
    }
}
