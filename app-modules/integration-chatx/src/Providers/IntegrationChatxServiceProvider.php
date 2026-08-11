<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Providers;

use Illuminate\Support\ServiceProvider;

class IntegrationChatxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/chatx.php', 'chatx');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/integration-chatx-routes.php');
    }
}
