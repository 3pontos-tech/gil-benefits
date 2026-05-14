<?php

declare(strict_types=1);

namespace TresPontosTech\Billing\Core\Listeners\Credit;

use App\Models\Users\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use TresPontosTech\Billing\Core\Events\Credit\CreditsDelivered;

class NotifyOwnerOfCreditsDeliveredListener implements ShouldQueue
{
    public function handle(CreditsDelivered $event): void
    {
        $owner = User::query()->findOrFail($event->ownerId);

        Notification::make()
            ->title(__('billing::notifications.credits_delivered.title'))
            ->body(__('billing::notifications.credits_delivered.body', ['quantity' => $event->quantity]))
            ->success()
            ->sendToDatabase($owner);
    }
}
