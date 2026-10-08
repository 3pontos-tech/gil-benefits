<?php

declare(strict_types=1);

namespace App\Notifications;

/**
 * Tom do aviso: define cor e ícone no sino do painel e no app.
 */
enum NotificationTone: string
{
    case Success = 'success';
    case Warning = 'warning';
    case Info = 'info';
    case Danger = 'danger';
}
