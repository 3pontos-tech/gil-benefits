<?php

declare(strict_types=1);

namespace TresPontosTech\Credits;

use Illuminate\Support\ServiceProvider;

class CreditsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'credits');
        $this->mergeConfigFrom(__DIR__ . '/../config/credits.php', 'credits');
    }
}
