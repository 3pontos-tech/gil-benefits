<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Listeners;

use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use TresPontosTech\Admin\Actions\GetAdminUsersAction;
use TresPontosTech\User\Events\UserRegistered;

class NotifyAdminsOfUserRegisteredListener implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        $admins = GetAdminUsersAction::execute();

        if ($admins->isEmpty()) {
            return;
        }

        $admins->each(
            fn (Model|Authenticatable|Collection|array $admin): Notification => Notification::make()
                ->title(__('panel-admin::notifications.user_registered.title'))
                ->body(__('panel-admin::notifications.user_registered.body', ['name' => $event->user->name]))
                ->info()
                ->sendToDatabase($admin),
        );
    }
}
