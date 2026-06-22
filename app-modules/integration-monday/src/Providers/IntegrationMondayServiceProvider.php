<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\IntegrationMonday\Events\MondayItemColumnChanged;
use TresPontosTech\IntegrationMonday\Listeners\PushTicketStatusToMonday;
use TresPontosTech\IntegrationMonday\Listeners\SyncTicketStatusFromMonday;
use TresPontosTech\Support\Events\SupportTicketStatusChanged;

class IntegrationMondayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/monday.php', 'monday');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/integration-monday-routes.php');

        Event::listen(MondayItemColumnChanged::class, SyncTicketStatusFromMonday::class);

        Event::listen(SupportTicketStatusChanged::class, PushTicketStatusToMonday::class);
    }
}
