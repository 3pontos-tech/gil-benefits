<?php

declare(strict_types=1);

namespace TresPontosTech\Support;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Override;
use TresPontosTech\Support\Events\ExternalTicketStatusChanged;
use TresPontosTech\Support\Listeners\ApplyExternalTicketStatus;

class SupportServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/support.php', 'support');
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'support');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'support');

        // An external destination (e.g. Monday) reported a status -> reconcile it.
        Event::listen(ExternalTicketStatusChanged::class, ApplyExternalTicketStatus::class);

        $this->publishes([
            __DIR__ . '/../config/support.php' => config_path('support.php'),
        ], 'support-config');
    }
}
