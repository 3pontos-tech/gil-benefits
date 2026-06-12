<?php

declare(strict_types=1);

namespace TresPontosTech\App\Providers;

use Illuminate\Support\ServiceProvider;
use TresPontosTech\App\Actions\BuildUserJourneyAction;

class PanelAppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Memoizado por request: JourneyHeroWidget e FinancialTopicsWidget consomem
        // a mesma jornada no mesmo carregamento, então compartilham uma única instância.
        $this->app->scoped(BuildUserJourneyAction::class);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'panel-app');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'panel-app');
    }
}
