<?php

namespace App\Providers;

use Basement\Webhooks\Models\InboundWebhook;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\Admin\Policies\InboundWebhookPolicy;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void {}

    public function register(): void
    {
        Relation::morphMap([
            'user' => config('auth.providers.users.model'),
        ]);
    }
}
