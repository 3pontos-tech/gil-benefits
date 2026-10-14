<?php

declare(strict_types=1);

namespace TresPontosTech\Api\Actions\Employee\Notifications;

use App\Models\Users\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Notifications\DatabaseNotification;

final readonly class MarkEmployeeNotificationReadAction
{
    public function __construct(private ListEmployeeNotificationsAction $listNotifications) {}

    /**
     * Marcar de novo não muda a data da primeira leitura.
     *
     * @throws ModelNotFoundException<DatabaseNotification>
     */
    public function handle(User $user, string $notificationId): DatabaseNotification
    {
        /** @var DatabaseNotification $notification */
        $notification = $this->listNotifications->handle($user)->whereKey($notificationId)->firstOrFail();

        $notification->markAsRead();

        return $notification;
    }
}
