<?php

declare(strict_types=1);

namespace App\Notifications;

/**
 * Tipo do aviso, gravado na coluna `notifications.type`. É por ele que o app sabe o que o
 * aviso significa e para onde o toque leva, sem depender do formato de nenhum canal.
 */
enum NotificationKind: string
{
    case AppointmentConfirmed = 'appointment_confirmed';
    case AppointmentCancelled = 'appointment_cancelled';
    case AppointmentCancelledLate = 'appointment_cancelled_late';
    case AppointmentCompleted = 'appointment_completed';
    case CreditsDelivered = 'credits_delivered';

    /**
     * Tipos que o app do colaborador exibe. Um aviso de outro público (admin, consultor),
     * mesmo que use esta estrutura, fica fora da central do app.
     *
     * @return list<self>
     */
    public static function shownInEmployeeApp(): array
    {
        return [
            self::AppointmentConfirmed,
            self::AppointmentCancelled,
            self::AppointmentCancelledLate,
            self::AppointmentCompleted,
            self::CreditsDelivered,
        ];
    }
}
