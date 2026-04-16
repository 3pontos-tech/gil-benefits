<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Listeners;

use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use TresPontosTech\Admin\Actions\GetAdminUsersAction;
use TresPontosTech\Appointments\Events\AppointmentCompleted;

class NotifyAdminsOfAppointmentCompletedListener implements ShouldQueue
{
    public function handle(AppointmentCompleted $event): void
    {
        $admins = GetAdminUsersAction::execute();

        if ($admins->isEmpty()) {
            return;
        }

        $event->appointment->loadMissing('user');

        $admins->each(
            fn (Model|Authenticatable|Collection|array $admin): Notification => Notification::make()
                ->title(__('panel-admin::notifications.appointment_completed.title'))
                ->body(__('panel-admin::notifications.appointment_completed.body', ['name' => $event->appointment->user->name]))
                ->success()
                ->sendToDatabase($admin),
        );
    }
}
