<?php

declare(strict_types=1);

namespace TresPontosTech\PanelAdmin\Listeners;

use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use TresPontosTech\IntegrationStripe\Events\StripeWebhookFailed;
use TresPontosTech\PanelAdmin\Actions\GetAdminUsersAction;

class NotifyAdminsOfStripeWebhookFailedListener implements ShouldQueue
{
    public function handle(StripeWebhookFailed $event): void
    {
        GetAdminUsersAction::execute()->each(
            fn (Model|Authenticatable|Collection|array $admin): Notification => Notification::make()
                ->title(__('panel-admin::notifications.stripe_webhook_failed.title'))
                ->body(__('panel-admin::notifications.stripe_webhook_failed.body', [
                    'type' => $event->type,
                    'event_id' => $event->eventId,
                ]))
                ->danger()
                ->sendToDatabase($admin),
        );
    }
}
