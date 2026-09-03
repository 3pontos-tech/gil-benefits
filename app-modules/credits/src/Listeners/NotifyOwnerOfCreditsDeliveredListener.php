<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Listeners;

use App\Models\Users\User;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use TresPontosTech\Credits\Events\CreditsDelivered;

class NotifyOwnerOfCreditsDeliveredListener implements ShouldQueue
{
    public function handle(CreditsDelivered $event): void
    {
        $owner = User::query()->findOrFail($event->ownerId);

        Notification::make()
            ->title(__('credits::notifications.credits_delivered.title'))
            ->body(__('credits::notifications.credits_delivered.body', ['quantity' => $event->quantity]))
            ->success()
            ->sendToDatabase($owner);
    }
}
