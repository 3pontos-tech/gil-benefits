<?php

declare(strict_types=1);

namespace TresPontosTech\PanelConsultant\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Consultants\Observers\MediaObserver;

class PanelConsultantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/panel-consultant.php', 'panel-consultant');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'panel-consultant');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'panel-consultant');
        Media::observe(MediaObserver::class);
    }
}
