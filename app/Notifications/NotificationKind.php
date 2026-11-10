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
    case AppointmentChanged = 'appointment_changed';
    case AppointmentCancelled = 'appointment_cancelled';
    case AppointmentCancelledLate = 'appointment_cancelled_late';
    case AppointmentCompleted = 'appointment_completed';
    case AppointmentReminder = 'appointment_reminder';
    case MaterialShared = 'material_shared';
    case CreditsDelivered = 'credits_delivered';
    case CreditsExpiring = 'credits_expiring';
    case AppointmentCancelledForConsultant = 'appointment_cancelled_for_consultant';
    case CreditOrderCreated = 'credit_order_created';
    case VoucherBatchPdfQueued = 'voucher_batch_pdf_queued';
    case VoucherBatchPdfReady = 'voucher_batch_pdf_ready';
    case VoucherBatchPdfFailed = 'voucher_batch_pdf_failed';

    /**
     * Tipos que o app do colaborador exibe. Um aviso de outro público (admin, consultor),
     * mesmo que use esta estrutura, fica fora da central do app. O lembrete do encontro não
     * entra: ele é só push, e a contagem regressiva já está na tela inicial.
     *
     * @return list<self>
     */
    public static function shownInEmployeeApp(): array
    {
        return [
            self::AppointmentConfirmed,
            self::AppointmentChanged,
            self::AppointmentCancelled,
            self::AppointmentCancelledLate,
            self::AppointmentCompleted,
            self::MaterialShared,
            self::CreditsDelivered,
            self::CreditsExpiring,
        ];
    }
}
