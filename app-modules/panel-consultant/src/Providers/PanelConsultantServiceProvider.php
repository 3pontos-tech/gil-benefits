<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Providers;

use Illuminate\Support\ServiceProvider;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Consultants\Observers\MediaObserver;

class PanelConsultantServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'panel-consultant');
        Media::observe(MediaObserver::class);
    }
}
