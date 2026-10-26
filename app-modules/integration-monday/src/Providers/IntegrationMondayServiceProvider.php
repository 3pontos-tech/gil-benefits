<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Providers;

use Illuminate\Support\ServiceProvider;
use TresPontosTech\IntegrationMonday\MondayClient;
use TresPontosTech\IntegrationMonday\Senders\MondayTicketSenderAdapter;
use TresPontosTech\Support\Contracts\TicketChannelSender;
use TresPontosTech\Support\Enums\TicketDestinationTypeEnum;
use TresPontosTech\Support\TicketSenderManager;

class IntegrationMondayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/monday.php', 'monday');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/integration-monday-routes.php');

        $this->registerTicketSender();
    }

    /**
     * Deferred to booted() because TicketSenderManager must already be bound as a
     * singleton by SupportServiceProvider: Manager::extend() stores the creator on
     * the instance, so a non-shared binding would drop it on the next resolve().
     */
    private function registerTicketSender(): void
    {
        $this->app->booted(function (): void {
            $this->app->make(TicketSenderManager::class)->extend(
                TicketDestinationTypeEnum::Monday->value,
                fn (): TicketChannelSender => new MondayTicketSenderAdapter($this->app->make(MondayClient::class)),
            );
        });
    }
}
