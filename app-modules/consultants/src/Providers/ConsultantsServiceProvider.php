<?php

declare(strict_types=1);

namespace TresPontosTech\Consultants\Providers;

use Illuminate\Support\ServiceProvider;

class ConsultantsServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'consultants');

    }
}
