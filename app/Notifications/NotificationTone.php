<?php

declare(strict_types=1);

namespace App\Notifications;

enum NotificationTone: string
{
    case Success = 'success';
    case Warning = 'warning';
    case Info = 'info';
    case Danger = 'danger';
}
