<?php

declare(strict_types=1);

namespace TresPontosTech\Credits\Listeners;

use App\Models\Users\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use TresPontosTech\Credits\Events\CreditsDelivered;
use TresPontosTech\Credits\Notifications\CreditsDeliveredNotification;

class NotifyOwnerOfCreditsDeliveredListener implements ShouldQueue
{
    public function handle(CreditsDelivered $event): void
    {
        $owner = User::query()->findOrFail($event->ownerId);

        $owner->notify(new CreditsDeliveredNotification($event->quantity));
    }
}
