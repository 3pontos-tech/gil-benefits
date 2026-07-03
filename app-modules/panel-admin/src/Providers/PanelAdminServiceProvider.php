<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Providers;

use Basement\BetterMails\Core\Models\BetterEmail;
use Basement\Webhooks\Models\InboundWebhook;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\PanelAdmin\Policies\BetterMailPolicy;
use TresPontosTech\PanelAdmin\Policies\InboundWebhookPolicy;

class PanelAdminServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'panel-admin');
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'panel-admin');

        Gate::policy(InboundWebhook::class, InboundWebhookPolicy::class);
        Gate::policy(BetterEmail::class, BetterMailPolicy::class);
    }
}
