<?php

declare(strict_types=1);

namespace TresPontosTech\IntegrationMonday\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use TresPontosTech\IntegrationMonday\Events\MondayItemColumnChanged;
use TresPontosTech\IntegrationMonday\Listeners\PushTicketStatusToMonday;
use TresPontosTech\IntegrationMonday\Listeners\SyncTicketStatusFromMonday;
use TresPontosTech\IntegrationMonday\Senders\MondayTicketSenderAdapter;
use TresPontosTech\Support\Events\SupportTicketStatusChanged;
use TresPontosTech\Support\Senders\MondayTicketSender;

class IntegrationMondayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/monday.php', 'monday');

        // The support dispatch pipeline resolves the Monday sender by its class
        // name; swap the support no-op for the real client-backed adapter.
        $this->app->bind(MondayTicketSender::class, MondayTicketSenderAdapter::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/integration-monday-routes.php');

        // Inbound: Monday board status change -> guarded ticket transition.
        Event::listen(MondayItemColumnChanged::class, SyncTicketStatusFromMonday::class);

        // Outbound: app-side status change -> push to the card. Listens to the
        // support domain event (not the model), keeping this module agnostic.
        Event::listen(SupportTicketStatusChanged::class, PushTicketStatusToMonday::class);
    }
}
