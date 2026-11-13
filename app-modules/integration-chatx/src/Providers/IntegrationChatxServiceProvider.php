<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationChatx\Providers;

use Illuminate\Support\ServiceProvider;
use TresPontosTech\IntegrationChatx\ChatxStatusClient;
use TresPontosTech\IntegrationChatx\Senders\ChatxNotificationChannel;
use TresPontosTech\Support\Contracts\TicketNotificationChannel;
use TresPontosTech\Support\Enums\TicketNotificationChannelEnum;
use TresPontosTech\Support\TicketNotificationManager;

class IntegrationChatxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/chatx.php', 'chatx');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/integration-chatx-routes.php');
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'integration-chatx');

        $this->registerNotificationChannel();
    }

    /**
     * Deferred to booted() because TicketNotificationManager must already be bound
     * as a singleton by SupportServiceProvider: Manager::extend() stores the creator
     * on the instance, so a non-shared binding would drop it on the next resolve().
     */
    private function registerNotificationChannel(): void
    {
        $this->app->booted(function (): void {
            $container = $this->app;

            $this->app->make(TicketNotificationManager::class)->extend(
                TicketNotificationChannelEnum::Whatsapp->value,
                fn (): TicketNotificationChannel => new ChatxNotificationChannel($container->make(ChatxStatusClient::class)),
            );
        });
    }
}
