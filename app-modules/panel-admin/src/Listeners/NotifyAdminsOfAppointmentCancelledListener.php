<?php

declare(strict_types=1);

namespace TresPontosTech\Admin\Listeners;

use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use TresPontosTech\Admin\Actions\GetAdminUsersAction;
use TresPontosTech\Appointments\Events\AppointmentCancelled;

class NotifyAdminsOfAppointmentCancelledListener implements ShouldQueue
{
    public function handle(AppointmentCancelled $event): void
    {
        $admins = GetAdminUsersAction::execute();

        if ($admins->isEmpty()) {
            return;
        }

        $event->appointment->loadMissing('user');

        $admins->each(
            fn (Model|Authenticatable|Collection|array $admin): Notification => Notification::make()
                ->title(__('panel-admin::notifications.appointment_cancelled.title'))
                ->body(__('panel-admin::notifications.appointment_cancelled.body', ['name' => $event->appointment->user?->name ?? __('panel-admin::notifications.unknown_user')]))
                ->warning()
                ->sendToDatabase($admin),
        );
    }
}
